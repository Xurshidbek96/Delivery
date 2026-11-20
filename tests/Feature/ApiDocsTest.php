<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Hujjat kod bilan sinxronligini kafolatlaydi: yangi endpoint qo'shilsa yoki javob maydoni o'zgarsa,
 * docs/openapi.yaml va Postman collection yangilanmaguncha bu test yiqiladi.
 */
class ApiDocsTest extends TestCase
{
    use RefreshDatabase;

    private const POSTMAN = 'docs/postman/Delivery.postman_collection.json';

    /** @return array<string, mixed> */
    private function spec(): array
    {
        return Yaml::parseFile(base_path('docs/openapi.yaml'));
    }

    /** @return list<string> "POST /client/orders/{}" ko'rinishida */
    private function apiRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => str_starts_with($r->uri(), 'api/v1/'))
            ->flatMap(fn (Route $r) => collect($r->methods())
                ->reject(fn (string $m) => $m === 'HEAD')
                ->map(fn (string $m) => $m.' '.$this->normalize(substr($r->uri(), strlen('api/v1')))))
            ->sort()->values()->all();
    }

    private function normalize(string $path): string
    {
        return preg_replace('/\{[^}]+\}|:\w+/', '{}', '/'.ltrim($path, '/'));
    }

    public function test_every_api_route_is_documented_and_nothing_extra(): void
    {
        $documented = collect($this->spec()['paths'])
            ->flatMap(fn (array $ops, string $path) => collect($ops)->keys()
                ->reject(fn ($k) => $k === 'parameters')
                ->map(fn ($m) => strtoupper($m).' '.$this->normalize($path)))
            ->sort()->values()->all();

        $this->assertSame([], array_values(array_diff($this->apiRoutes(), $documented)), 'openapi.yaml da yo\'q endpointlar');
        $this->assertSame([], array_values(array_diff($documented, $this->apiRoutes())), 'openapi.yaml da ortiqcha endpointlar');
    }

    public function test_all_openapi_refs_resolve(): void
    {
        $spec = $this->spec();
        preg_match_all("/\\\$ref: '#\\/([^']+)'/", file_get_contents(base_path('docs/openapi.yaml')), $m);

        foreach (array_unique($m[1]) as $ref) {
            $node = $spec;
            foreach (explode('/', $ref) as $part) {
                $this->assertArrayHasKey($part, $node, "Topilmagan \$ref: #/{$ref}");
                $node = $node[$part];
            }
        }
    }

    public function test_postman_collection_covers_every_route(): void
    {
        $walk = function (array $items) use (&$walk): array {
            return collect($items)->flatMap(fn (array $i) => isset($i['item'])
                ? $walk($i['item'])
                : [$i['request']['method'].' '.$this->normalize(preg_replace(['/^\{\{base_url\}\}/', '/\?.*$/', '/\{\{\w+\}\}/'], ['', '', '{}'], $i['request']['url']['raw']))])
                ->all();
        };

        $collection = json_decode(file_get_contents(base_path(self::POSTMAN)), true, flags: JSON_THROW_ON_ERROR);
        $covered = array_unique($walk($collection['item']));

        $this->assertSame([], array_values(array_diff($this->apiRoutes(), $covered)), 'Postman collection\'da yo\'q endpointlar');
    }

    public function test_order_and_user_responses_match_schema(): void
    {
        $schemas = $this->spec()['components']['schemas'];
        $client = User::factory()->client()->create();
        $order = $this->placeOrder($client);

        $this->actingAsApi($client);
        $orderKeys = array_keys($this->getJson("/api/v1/client/orders/{$order->id}")->json('data'));
        $itemKeys = array_keys($this->getJson("/api/v1/client/orders/{$order->id}")->json('data.items.0'));
        $userKeys = array_keys($this->getJson('/api/v1/me')->json('data'));

        $courier = User::factory()->courier()->create();
        $courierKeys = array_keys($this->actingAsApi($courier)->getJson('/api/v1/me')->json('data'));

        $this->assertSame([], array_diff($orderKeys, array_keys($schemas['Order']['properties'])), 'Order sxemasida yo\'q maydonlar');
        $this->assertSame([], array_diff($itemKeys, array_keys($schemas['OrderItem']['properties'])), 'OrderItem sxemasida yo\'q maydonlar');
        $this->assertSame([], array_diff([...$userKeys, ...$courierKeys], array_keys($schemas['User']['properties'])), 'User sxemasida yo\'q maydonlar');
    }

    public function test_swagger_ui_and_spec_are_served(): void
    {
        $this->get('/docs/api')->assertOk()->assertSee('swagger-ui');
        $this->get('/docs/openapi.yaml')->assertOk()->assertHeader('Content-Type', 'application/yaml; charset=UTF-8');
    }
}
