<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">&larr;</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->name }}</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Status</dt>
                        <dd class="text-gray-900">{{ $project->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Objective</dt>
                        <dd class="text-gray-900">{{ $project->objective ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">Description</dt>
                        <dd class="text-gray-900">{{ $project->description ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Repositories</h3>
                @forelse ($project->repositories as $repo)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <span class="font-medium">{{ $repo->name }}</span>
                        <span class="text-gray-500">({{ $repo->role }})</span>
                        &mdash; {{ $repo->provider }}:{{ $repo->repository }}
                        <span class="text-gray-500">[{{ $repo->default_branch }}]</span>
                        @if ($repo->local_path)
                            <div class="text-gray-500">{{ $repo->local_path }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">None recorded.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Environments</h3>
                @forelse ($project->environments as $env)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <span class="font-medium">{{ $env->name }}</span>
                        @if ($env->domain) &mdash; {{ $env->domain }} @endif
                        @if ($env->server) <span class="text-gray-500">({{ $env->server }})</span> @endif
                        @if ($env->notes) <div class="text-gray-500">{{ $env->notes }}</div> @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">None recorded.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Decisions</h3>
                @forelse ($project->decisions as $decision)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <span class="font-medium">{{ $decision->title }}</span>
                        @if ($decision->decided_at) <span class="text-gray-500">({{ $decision->decided_at->format('Y-m-d') }})</span> @endif
                        <div class="text-gray-700">{{ $decision->decision }}</div>
                        @if ($decision->rationale) <div class="text-gray-500">Rationale: {{ $decision->rationale }}</div> @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">None recorded.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Knowledge</h3>
                @forelse ($project->knowledgeItems as $item)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $item->type }}</span>
                        <span class="font-medium">{{ $item->title }}</span>
                        <div class="text-gray-700">{{ $item->body }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">None recorded.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Recent audit events</h3>
                @forelse ($project->auditEvents as $event)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <span class="text-gray-500">{{ $event->created_at->format('Y-m-d H:i') }}</span>
                        <span class="font-medium">{{ $event->actor }}</span>
                        &mdash; {{ $event->action }}
                        @if ($event->description) <div class="text-gray-700">{{ $event->description }}</div> @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">None recorded.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
