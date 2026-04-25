<?php

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\EducationLevel;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\Province;
use App\Models\Sex;
use App\Models\TypeIdentification;
use Inertia\Inertia;
use Modules\People\Http\Requests\StorePersonRequest;
use Modules\People\Http\Requests\UpdatePersonRequest;
use Modules\People\Models\Person;
use Modules\People\Services\PersonService;

class PeopleController extends Controller
{
    public function __construct(
        private PersonService $personService
    ) {}

    public function index()
    {
        $people = Person::query()
            ->with(['sex', 'typeIdentification', 'maritalStatus', 'nationality', 'educationLevel', 'country', 'province', 'city'])
            ->orderBy('first_surname')
            ->orderBy('second_surname')
            ->get()
            ->map(fn ($person) => [
                'id'                    => $person->id,
                'full_name'             => $person->full_name,
                'first_name'            => $person->first_name,
                'second_name'           => $person->second_name,
                'first_surname'         => $person->first_surname,
                'second_surname'        => $person->second_surname,
                'identification_number' => $person->identification_number,
                'type_identification'   => $person->typeIdentification?->name,
                'phone'                 => $person->phone,
                'cellphone'             => $person->cellphone,
                'birthdate'             => $person->birthdate?->format('Y-m-d'),
                'place_birth'           => $person->place_birth,
                'sex'                   => $person->sex?->name,
                'marital_status'        => $person->maritalStatus?->name,
                'nationality'           => $person->nationality?->name,
                'education_level'       => $person->educationLevel?->name,
                'main_street'           => $person->main_street,
                'secondary_street'      => $person->secondary_street,
                'neighborhood'          => $person->neighborhood,
                'reference'             => $person->reference,
                'country'               => $person->country?->name,
                'province'              => $person->province?->name,
                'city'                  => $person->city?->name,
                'created_at'            => $person->created_at->format('Y-m-d H:i'),

                'sex_id'                 => $person->sex_id,
                'type_identification_id' => $person->type_identification_id,
                'marital_status_id'      => $person->marital_status_id,
                'nationality_id'         => $person->nationality_id,
                'education_level_id'     => $person->education_level_id,
                'countries_id'           => $person->countries_id,
                'provinces_id'           => $person->provinces_id,
                'cities_id'              => $person->cities_id,
            ]);

        return Inertia::render('persons/Index', array_merge(
            compact('people'),
            $this->getFormData()
        ));
    }

    public function store(StorePersonRequest $request)
    {
        $this->personService->create($request->validated());

        return redirect()
            ->route('people.index')
            ->with('success', 'Persona creada exitosamente');
    }

    public function edit(Person $person)
    {
        return Inertia::render('persons/Edit', array_merge(
            $this->getFormData(),
            [
                'person' => [
                    'id'                    => $person->id,
                    'first_name'            => $person->first_name,
                    'second_name'           => $person->second_name,
                    'first_surname'         => $person->first_surname,
                    'second_surname'        => $person->second_surname,
                    'identification_number' => $person->identification_number,
                    'phone'                 => $person->phone,
                    'cellphone'             => $person->cellphone,
                    'birthdate'             => $person->birthdate?->format('Y-m-d'),
                    'place_birth'           => $person->place_birth,
                    'main_street'           => $person->main_street,
                    'secondary_street'      => $person->secondary_street,
                    'neighborhood'          => $person->neighborhood,
                    'reference'             => $person->reference,
                    'sex_id'                => $person->sex_id,
                    'type_identification_id'=> $person->type_identification_id,
                    'marital_status_id'     => $person->marital_status_id,
                    'nationality_id'        => $person->nationality_id,
                    'education_level_id'    => $person->education_level_id,
                    'countries_id'          => $person->countries_id,
                    'provinces_id'          => $person->provinces_id,
                    'cities_id'             => $person->cities_id,
                ],
            ]
        ));
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $this->personService->update($person, $request->validated());

        return redirect()
            ->route('people.index')
            ->with('success', 'Persona actualizada exitosamente');
    }

    public function destroy(Person $person)
    {
        try {
            $this->personService->delete($person);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('people.index')
            ->with('success', 'Persona eliminada exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'sexes'               => Sex::orderBy('name')->get(['id', 'name']),
            'typeIdentifications' => TypeIdentification::orderBy('name')->get(['id', 'name']),
            'maritalStatuses'     => MaritalStatus::orderBy('name')->get(['id', 'name']),
            'nationalities'       => Nationality::orderBy('name')->get(['id', 'name']),
            'educationLevels'     => EducationLevel::orderBy('name')->get(['id', 'name']),
            'countries'           => Country::orderBy('name')->get(['id', 'name']),
            'provinces'           => Province::orderBy('name')->get(['id', 'name', 'country_id']),
            'cities'              => City::orderBy('name')->get(['id', 'name', 'province_id']),
        ];
    }
}