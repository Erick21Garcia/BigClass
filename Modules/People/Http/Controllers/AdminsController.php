<?php

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\People\Http\Requests\StoreAdminRequest;
use Modules\People\Http\Requests\UpdateAdminRequest;
use Modules\People\Models\Admin;
use Modules\People\Models\Person;
use Modules\People\Services\AdminService;
use Modules\Institucion\Models\Institution;

class AdminsController extends Controller
{
    public function __construct(
        private AdminService $adminService
    ) {}

    public function index()
    {
        $admins = Admin::query()
            ->with(['person', 'institution'])
            ->orderBy('id')
            ->get()
            ->map(fn ($admin) => [
                'id'                    => $admin->id,
                'full_name'             => $admin->person?->full_name,
                'identification_number' => $admin->person?->identification_number,
                'institution'           => $admin->institution?->name,
                'position'              => $admin->position,
                'active'                => $admin->active,
                'created_at'            => $admin->created_at->format('Y-m-d H:i'),

                'person_id'             => $admin->person_id,
                'institution_id'        => $admin->institution_id,
            ]);

        return Inertia::render('admins/Index', array_merge(
            compact('admins'),
            $this->getFormData()
        ));
    }

    public function store(StoreAdminRequest $request)
    {
        $this->adminService->create($request->validated());

        return redirect()
            ->route('admins.index')
            ->with('success', 'Administrador creado exitosamente');
    }

    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        $this->adminService->update($admin, $request->validated());

        return redirect()
            ->route('admins.index')
            ->with('success', 'Administrador actualizado exitosamente');
    }

    public function destroy(Admin $admin)
    {
        try {
            $this->adminService->delete($admin);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('admins.index')
            ->with('success', 'Administrador eliminado exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'people'       => Person::orderBy('first_surname')
                                ->orderBy('second_surname')
                                ->get(['id', 'first_name', 'second_name', 'first_surname', 'second_surname', 'identification_number'])
                                ->map(fn ($p) => [
                                    'id'                    => $p->id,
                                    'name'                  => $p->full_name,
                                    'identification_number' => $p->identification_number,
                                ]),
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
        ];
    }
}