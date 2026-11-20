#!/usr/bin/env bash
# Serverdagi deploy (delivery.firstcoder.uz). GitHub Actions SSH orqali chaqiradi:
# authorized_keys'da shu kalit uchun `command="…/deploy/deploy.sh"` — kalit boshqa buyruq bajara olmaydi.
# Qo'lda: bash ~/web/delivery.firstcoder.uz/public_html/deploy/deploy.sh
#
# Butun tana funksiya ichida: `git merge` shu faylni yangilasa ham bash eski nusxani oxirigacha bajaradi.

main() {
    set -euo pipefail
    cd "$(dirname "$(readlink -f "$0")")/.."

    local php=/usr/bin/php8.4
    local composer="$php /usr/local/bin/composer"

    echo "==> $(date '+%F %T') deploy: $(pwd)"

    # Serverda qo'lda o'zgartirilgan tracked fayl bo'lsa — jimgina yo'qotmaymiz
    if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
        echo "Serverda commit qilinmagan o'zgarishlar bor — deploy to'xtatildi:"
        git status --short --untracked-files=no
        exit 1
    fi

    git fetch --quiet origin main
    local before after
    before=$(git rev-parse HEAD)
    git merge --ff-only --quiet origin/main
    after=$(git rev-parse HEAD)
    echo "==> $before -> $after"

    $composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress --quiet
    $composer clear-cache --quiet

    $php artisan migrate --force
    [ -L public/storage ] || $php artisan storage:link
    $php artisan optimize

    echo "==> tayyor: $(git log -1 --format='%h %s')"
}

main "$@"
exit
