<?php

namespace App\Http\Requests\Admin\System;

class UpdateInfrastructureConnectionRequest extends StoreInfrastructureConnectionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['type']);
        $rules['remove_credentials'] = ['nullable', 'array'];
        $rules['remove_credentials.*'] = ['string', 'distinct', 'max:100'];

        return $rules;
    }
}
