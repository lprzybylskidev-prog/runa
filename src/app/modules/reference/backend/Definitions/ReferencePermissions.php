<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\ManagementPermission;

/** Source dictionary; synchronization never assigns these grants to a user. */
final class ReferencePermissions
{
    /** @return list<ManagementPermission> */
    public static function permissions(): array
    {
        $labels = [
            'samples.read' => ['Odczyt rekordów', 'Read samples'],
            'samples.write' => ['Edycja rekordów', 'Edit samples'],
            'samples.code.read' => ['Odczyt kodu', 'Read sample code'],
            'samples.code.write' => ['Zmiana kodu', 'Change sample code'],
            'settings.read' => ['Odczyt ustawień', 'Read settings'],
            'settings.write' => ['Publikacja ustawień', 'Publish settings'],
            'settings.credential.read' => ['Odczyt stanu sekretu', 'Read secret set state'],
            'settings.credential.write' => ['Zastąpienie lub usunięcie sekretu', 'Replace or clear secret'],
            'history.read' => ['Odczyt historii przykładu', 'Read reference history'],
            'work.read' => ['Odczyt zadań', 'Read work'],
            'work.dispatch' => ['Uruchomienie zadania', 'Dispatch work'],
        ];

        return array_map(static fn (string $key, array $names) => new ManagementPermission($key,
            ['pl' => $names[0], 'en' => $names[1]], ['pl' => $names[0].'.', 'en' => $names[1].'.']), array_keys($labels), array_values($labels));
    }
}
