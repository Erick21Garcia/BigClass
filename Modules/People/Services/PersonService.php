<?php

namespace Modules\People\Services;

use Modules\People\Models\Person;

class PersonService
{
    public function create(array $data): Person
    {
        return Person::create($data);
    }

    public function update(Person $person, array $data): Person
    {
        $person->update($data);

        return $person;
    }

    public function delete(Person $person): void
    {
        $person->delete();
    }
}