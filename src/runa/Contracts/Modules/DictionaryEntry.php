<?php

namespace Runa\Contracts\Modules;

/** Explicit projection from immutable source localization; persistence belongs to the owner. */
final readonly class DictionaryEntry
{
    public function __construct(public string $code, public string $namePl, public string $nameEn, public string $descriptionPl, public string $descriptionEn)
    {
        new ApiOperation($code);
        ManagementAction::localization(['pl' => $namePl, 'en' => $nameEn], ['pl' => $descriptionPl, 'en' => $descriptionEn], 1);
    }

    /** @return array{code: string, name_pl: string, name_en: string, description_pl: string, description_en: string} */
    public function toRow(): array
    {
        return ['code' => $this->code, 'name_pl' => $this->namePl, 'name_en' => $this->nameEn, 'description_pl' => $this->descriptionPl, 'description_en' => $this->descriptionEn];
    }

    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     */
    public static function fromLocalized(string $code, array $names, array $descriptions): self
    {
        ManagementAction::localization($names, $descriptions, 1);

        return new self($code, $names['pl'], $names['en'], $descriptions['pl'], $descriptions['en']);
    }
}
