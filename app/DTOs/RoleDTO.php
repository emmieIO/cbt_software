<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RoleDTO
{
    /**
     * @param  string[]  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions = []
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
            permissions: $request->array('permissions')
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'permissions' => $this->permissions,
        ];
    }
}
