<?php

declare(strict_types=1);

namespace App\Domain\Organization\DTOs;

use Illuminate\Http\Request;

final class CreateOrganizationDTO
{
    public string $name;
    public ?string $description;

    public function __construct(string $name, ?string $description = null)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->string('description')->toString() ?: null,
        );
    }
}
