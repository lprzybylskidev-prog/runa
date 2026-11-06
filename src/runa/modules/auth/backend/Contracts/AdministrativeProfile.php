<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class AdministrativeProfile
{
    /**
     * @param array{email:string,verified:bool,verification_required:bool}|null $primaryEmail
     * @param list<array{public_id:string,email:string,verified:bool}> $secondaryEmails */
    public function __construct(public string $publicId, public int $version,
        public ?string $name, public ?string $firstName, public ?string $surname, public ?string $login,
        public ?array $primaryEmail, public array $secondaryEmails, public int $preferenceRevision)
    {
        new TrustedPrincipal($publicId);
        Values::list($secondaryEmails);
        if ($version < 1 || $preferenceRevision < 0 || count($secondaryEmails) > 100) {
            throw new \InvalidArgumentException('Invalid administrative profile.');
        }
    }
}
