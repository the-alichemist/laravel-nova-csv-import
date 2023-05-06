<?php

namespace SimonHamp\LaravelNovaCsvImport\Http\Controllers;

use Laravel\Nova\Resource;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Rules\Relatable;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

class ResourceFieldsController
{
    public function index(NovaRequest $request): array
    {

        $novaResource = $request->newResource();
        $fieldsCollection = collect($novaResource->creationFields($request));

        if (method_exists($novaResource, 'excludeAttributesFromImport')) {
            $fieldsCollection = $fieldsCollection->filter(function (Field $field) use ($novaResource, $request) {
                return !in_array($field->attribute, $novaResource::excludeAttributesFromImport($request));
            });
        }

        $fieldsCollection = $fieldsCollection->filter(function ($field) {
            return !$field->isRelationship();
        });

        return $fieldsCollection->map(function (Field $field) use ($novaResource, $request) {
            return [
                'name' => $field->name,
                'attribute' => $field->attribute,
                'rules' => $this->extractValidationRules($novaResource, $request)->get($field->attribute),
            ];
        })->values()->all();

        // Note: ->values() is used here to avoid this array being turned into an object due to
        // non-sequential keys (which might happen due to the filtering above.
        // return [
        //     $novaResource->uriKey() => $fields->values(),
        // ];
    }

    protected function extractValidationRules(Resource $resource, NovaRequest $request): Collection
    {
        return collect($resource::rulesForCreation($request))->mapWithKeys(function ($rule, $key) {
            foreach ($rule as $i => $r) {
                if (!is_object($r)) {
                    continue;
                }

                // Make sure relation checks start out with a clean query
                if (is_a($r, Relatable::class)) {
                    $rule[$i] = function () use ($r) {
                        $r->query = $r->query->newQuery();

                        return $r;
                    };
                }
            }

            return [$key => $rule];
        });
    }
}
