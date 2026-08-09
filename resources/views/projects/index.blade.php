<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-200">
                    @forelse ($projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="block p-6 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $project->name }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @class([
                                                'bg-green-100 text-green-800' => $project->status === 'active',
                                                'bg-blue-100 text-blue-800' => $project->status === 'beta',
                                                'bg-yellow-100 text-yellow-800' => $project->status === 'paused',
                                                'bg-gray-100 text-gray-800' => $project->status === 'archived',
                                            ])">
                                            {{ $project->status }}
                                        </span>
                                    </div>
                                    @if ($project->objective)
                                        <p class="mt-1 text-sm text-gray-600">{{ $project->objective }}</p>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 text-right shrink-0 ml-4">
                                    <div>{{ $project->repositories_count }} repos</div>
                                    <div>{{ $project->environments_count }} environments</div>
                                    <div>{{ $project->decisions_count }} decisions</div>
                                    <div>{{ $project->knowledge_items_count }} knowledge items</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 text-gray-500">No projects onboarded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
