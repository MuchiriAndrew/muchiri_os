<?php

namespace App\Http\Controllers;

use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount(['repositories', 'environments', 'decisions', 'knowledgeItems'])
            ->orderBy('name')
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load(['repositories', 'environments', 'decisions', 'knowledgeItems', 'auditEvents' => function ($query) {
            $query->latest()->limit(20);
        }]);

        return view('projects.show', compact('project'));
    }
}
