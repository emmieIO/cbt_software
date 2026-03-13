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
        public string $category = 'staff',
        public array $permissions = []
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name'),
            category: $request->string('category', 'staff'),
            permissions: $request->array('permissions')
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category' => $this->category,
            'permissions' => $this->permissions,
        ];
    }
}
