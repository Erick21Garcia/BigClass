<?php

namespace Modules\LMS\Http\Requests\Concerns;

use Modules\Academic\Models\Section;

trait AuthorizesTeacherOrAdmin
{
    /**
     * Admin/super-admin siempre puede. Si no, solo el docente asignado
     * a esa Section específica (vía Section->teacher->person->user_id).
     */
    protected function userCanManageSection(?Section $section): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole('admin', 'super-admin')) {
            return true;
        }

        if (! $section) {
            return false;
        }

        return $section->teacher?->person?->user_id === $user->id;
    }
}