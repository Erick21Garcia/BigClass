<?php

namespace Modules\People\Services;

use Modules\People\Models\Admin;

class AdminService
{
    public function create(array $data): Admin
    {
        return Admin::create($data);
    }

    public function update(Admin $admin, array $data): Admin
    {
        $admin->update($data);

        return $admin->fresh();
    }

    public function delete(Admin $admin): void
    {
        $admin->delete();
    }
}