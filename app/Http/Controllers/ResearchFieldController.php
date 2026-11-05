<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\ResearchFieldRequest;
use App\Models\ResearchField;
use App\Services\ResearchFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Kelola Bidang Keahlian (admin). Akses dijaga middleware auth + role:admin pada grup rute. */
class ResearchFieldController extends Controller
{
    public function __construct(private readonly ResearchFieldService $fields)
    {
    }

    public function index(): View
    {
        return view('roles.admin.research-fields', ['fields' => $this->fields->list()]);
    }

    public function store(ResearchFieldRequest $request): RedirectResponse
    {
        $field = $this->fields->create($request->validated('name'), $request->user());

        return redirect()->route('admin.fields.index')->with('success', "Bidang \"{$field->name}\" ditambahkan.");
    }

    public function update(ResearchFieldRequest $request, ResearchField $field): RedirectResponse
    {
        $this->fields->rename($field, $request->validated('name'), $request->user());

        return redirect()->route('admin.fields.index')->with('success', 'Bidang keahlian diperbarui.');
    }
}
