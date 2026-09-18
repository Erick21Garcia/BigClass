<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use App\Models\User;
use Modules\Institucion\Models\Career;

class Applicant extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'applicants';

    protected $fillable = [
        'user_id',
        'career_id',
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'identification_number',
        'phone',
        'cellphone',
        'birthdate',
        'place_birth',
        'main_street',
        'secondary_street',
        'neighborhood',
        'reference',
        'marital_status_id',
        'type_identification_id',
        'sex_id',
        'nationality_id',
        'education_level_id',
        'countries_id',
        'provinces_id',
        'cities_id',
        'status',
        'submitted_at',
        'last_activity_at',
        'decided_at',
        'decided_by',
        'decision_notes',
    ];

    protected $casts = [
        'birthdate'         => 'date',
        'submitted_at'      => 'datetime',
        'last_activity_at'  => 'datetime',
        'decided_at'        => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->second_name} {$this->first_surname} {$this->second_surname}");
    }

    public function scopeInKanban($query)
    {
        return $query->whereIn('status', ['recibida', 'en_revision', 'aprobada', 'rechazada']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'career_id', 'submitted_at', 'decided_at', 'decision_notes'])
            ->logOnlyDirty();
    }
}