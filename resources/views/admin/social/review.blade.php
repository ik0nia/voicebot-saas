@extends('layouts.admin')

@section('title', 'Review coadă social')

@push('head')
<style>
    body { background: #F5F1E8; }
    .review-wrap {
        max-width: 880px;
        margin: 24px auto 80px;
        padding: 0 16px;
    }
    .review-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .review-head h1 {
        font-family: 'Instrument Sans', 'Inter', sans-serif;
        font-weight: 500;
        font-size: 1.6rem;
        letter-spacing: -0.02em;
        color: #1C1917;
        margin: 0;
    }
    .counter-pill {
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #78716C;
        padding: 6px 12px;
        border-radius: 999px;
        background: #FAF7EF;
        border: 1px solid #E7E0CE;
    }
    .card {
        background: #FAF7EF;
        border-radius: 24px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
        padding: 24px;
        margin-bottom: 24px;
    }
    .card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #78716C;
        margin-bottom: 16px;
    }
    .platform-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.7rem;
        letter-spacing: 0.08em;
    }
    .platform-facebook { background: #1877F2; color: #fff; }
    .platform-instagram { background: linear-gradient(45deg, #F58529, #DD2A7B, #8134AF, #515BD4); color: #fff; }
    .image-frame {
        border-radius: 16px;
        overflow: hidden;
        margin: 0 0 16px;
        background: #EFE5D0;
        position: relative;
    }
    .image-frame img {
        display: block;
        width: 100%;
        height: auto;
    }
    .image-missing {
        padding: 40px;
        text-align: center;
        color: #78716C;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.85rem;
    }
    .content-text {
        color: #1C1917;
        font-size: 0.95rem;
        line-height: 1.55;
        white-space: pre-wrap;
        font-family: 'Inter', sans-serif;
        margin: 0 0 12px;
    }
    .hashtags {
        color: #00376B;
        font-size: 0.82rem;
        margin-bottom: 16px;
    }
    .siblings-note {
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.72rem;
        color: #78716C;
        background: #F5F1E8;
        border: 1px dashed #E7E0CE;
        padding: 8px 12px;
        border-radius: 12px;
        margin-bottom: 16px;
    }
    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn {
        border: 0;
        padding: 12px 20px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform .08s ease;
    }
    .btn:active { transform: scale(0.96); }
    .btn-approve { background: #10B981; color: #fff; }
    .btn-approve:hover { background: #059669; }
    .btn-reject { background: transparent; color: #991B1B; border: 1px solid #F5C7C7; }
    .btn-reject:hover { background: #FEE2E2; }
    .btn-edit { background: #1C1917; color: #fff; }
    .btn-edit:hover { background: #3A3532; }
    .btn-regen { background: #FAF7EF; color: #78716C; border: 1px solid #E7E0CE; }
    .btn-regen:hover { background: #EFE5D0; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #78716C;
    }
    .empty-state .mark {
        width: 72px; height: 72px; margin: 0 auto 16px;
        background: #DC2626; border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 600; font-size: 1.8rem;
    }
    .empty-state p { font-size: 1rem; line-height: 1.5; }
    .empty-state code { font-family: 'JetBrains Mono', monospace; color: #1C1917; background: #FAF7EF; padding: 2px 6px; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="review-wrap">
    <div class="review-head">
        <h1>Coadă review</h1>
        <span class="counter-pill">{{ $total }} {{ $total === 1 ? 'postare' : 'postări' }} de aprobat</span>
    </div>

    @if($posts->isEmpty())
        <div class="card empty-state">
            <div class="mark">✓</div>
            <p>Nu există postări de aprobat în acest moment.<br>
            Cron-ul <code>social:ensure-drafts --target=10</code> completează coada la fiecare 15 min.</p>
        </div>
    @else
        @foreach($posts as $post)
            <article class="card" id="post-{{ $post->id }}">
                <div class="card-meta">
                    <div>
                        <span class="platform-badge platform-{{ $post->platform }}">{{ $post->platform }}</span>
                        &nbsp;&middot;&nbsp;{{ $post->post_type }}
                        &nbsp;&middot;&nbsp;#{{ $post->id }}
                    </div>
                    <div>{{ optional($post->created_at)->diffForHumans() }}</div>
                </div>

                <div class="image-frame">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" alt="post {{ $post->id }}" loading="lazy">
                    @else
                        <div class="image-missing">Fără imagine încă</div>
                    @endif
                </div>

                <div class="content-text">{{ $post->content }}</div>

                @if(!empty($post->hashtags))
                    <div class="hashtags">
                        @foreach((array) $post->hashtags as $tag)
                            <span>#{{ ltrim($tag, '#') }}</span>
                        @endforeach
                    </div>
                @endif

                @php
                    $siblings = $post->group_id
                        ? \App\Models\SocialPost::where('group_id', $post->group_id)
                            ->where('id', '!=', $post->id)->get()
                        : collect();
                @endphp
                @if($siblings->isNotEmpty())
                    <div class="siblings-note">
                        Grup cu: {{ $siblings->map(fn($s) => $s->platform.'/'.$s->post_type)->implode(' + ') }}
                        &middot; aprobarea / respingerea se propagă automat
                    </div>
                @endif

                <div class="actions">
                    <form method="POST" action="{{ route('admin.social.approve', $post) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-approve">✓ Aprobă</button>
                    </form>
                    <a href="{{ route('admin.social.edit', $post) }}" class="btn btn-edit">✎ Editează</a>
                    <form method="POST" action="{{ route('admin.social.regenerateImage', $post) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-regen">↻ Regenerează imagine</button>
                    </form>
                    <form method="POST" action="{{ route('admin.social.reject', $post) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-reject">✗ Respinge</button>
                    </form>
                </div>
            </article>
        @endforeach
    @endif
</div>
@endsection
