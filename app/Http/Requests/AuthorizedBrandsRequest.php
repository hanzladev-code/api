<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizedBrandsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "brand_name" => "required|string|unique:authorized_brands,brand_name," . ($this->authorized_brand ? $this->authorized_brand->id : null),
            "brand_image" => "nullable|image|max:2048",
            "contact_person" => "required|string",
            "contact_number" => "required|string",
            "email" => "required|email",
            "address" => "required|string",
            "warranty_terms" => "nullable|string",
            "installation_terms" => "nullable|string",
            "commission_rates" => "nullable|string",
            "status" => "required|string|in:active,not_authorized,on_hold,inactive",
            "provided_items" => "nullable|array",
            "offers" => "nullable|array",
            "offers.*.title" => "required|string",
            "offers.*.description" => "required|string",
            "offers.*.valid_until" => "required|date",
            "crm_access" => "nullable",
            "crm_access.crm_url" => "nullable|string|url",
            "crm_access.app_name" => "nullable|string",
            "crm_access.credentials" => "nullable|array",
            "crm_access.credentials.*.username" => "required|string",
            "crm_access.credentials.*.password" => "required|string",
            "crm_access.credentials.*.role" => "required|string",
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brandName' => 'brand name',
            'brandImage' => 'brand logo',
            'contactPerson' => 'contact person',
            'contactNumber' => 'contact number',
            'email' => 'email',
            'address' => 'address',
            'servicesOffered' => 'services offered',
            'specialPackages' => 'special packages',
            'warrantyTerms' => 'warranty terms',
            'installationTerms' => 'installation terms',
            'commissionRates' => 'commission rates',
            'status' => 'status',
            'providedItems' => 'provided items',
            'providedItems.*.name' => 'item name',
            'providedItems.*.quantity' => 'item quantity',
            'offers.*.title' => 'offer title',
            'offers.*.description' => 'offer description',
            'offers.*.validUntil' => 'offer validity date',
            'crmAccess.crmUrl' => 'CRM URL',
            'crmAccess.appName' => 'application name',
            'crmAccess.credentials.*.username' => 'username',
            'crmAccess.credentials.*.password' => 'password',
            'crmAccess.credentials.*.role' => 'role',
        ];
    }
}
