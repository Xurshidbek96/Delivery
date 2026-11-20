@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Xodimlar</h1>
            <p class="page-subtitle">Moderator — buyurtmalar va kuryerlar; Administrator — qo'shimcha xodimlar va sozlamalar</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Xodim qo'shish</a>
        </div>
    </div>

    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Xodim</th><th>Rol</th><th>Oxirgi kirish</th><th>Holat</th><th class="shrink"></th></tr></thead>
                <tbody>
                @foreach($staff as $s)
                    <tr>
                        <td>
                            <div class="cell-user">
                                <span class="avatar">{{ $s->initials() }}</span>
                                <div><div class="cell-main">{{ $s->name }} @if($s->is(auth()->user()))<span class="subtle">(siz)</span>@endif</div><div class="cell-sub">{{ \App\Support\Phone::format($s->phone) }}</div></div>
                            </div>
                        </td>
                        <td><span @class(['badge', 'badge-brand' => $s->isAdmin(), 'badge-info' => ! $s->isAdmin()])>{{ $s->role->label() }}</span></td>
                        <td>{{ $s->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td>@if($s->is_active)<span class="badge badge-success">Faol</span>@else<span class="badge badge-danger">Bloklangan</span>@endif</td>
                        <td class="shrink"><a href="{{ route('admin.staff.edit', $s) }}" class="btn btn-sm btn-ghost" aria-label="Tahrirlash"><i class="ti ti-pencil"></i></a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
