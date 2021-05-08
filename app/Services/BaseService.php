<?php

namespace Warehouse\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

abstract class BaseService
{
    const AUTHORITY_ADMIN='admin';

    const AUTHORITY_CUSTOMER='customer';

    const AUTHORITY_SYSTEM='system';

    private $authority=self::AUTHORITY_CUSTOMER;

    public function setAuthority($authority)
    {
        $this->authority = $authority;
        return $this;
    }

    protected function getAuthority()
    {
        return $this->authority;
    }

    protected function checkAuthority($authority)
    {
        if(($this->getAuthority() == self::AUTHORITY_SYSTEM))
            return true;
        return ($this->getAuthority() == $authority);
    }

    protected function createSlug($model, $name, $count = 0)
    {
        $slug = str_slug($name);
        if ($count > 0) {
            $slug .= '_' . $count;
        }
        $attribute = $model::where('slug', $slug)->first();
        if (!empty($attribute)) {
            return $this->createSlug($model, $slug, ++$count);
        }
        return $slug;
    }

    protected function upload($file, $path, $fileName)
    {
        Storage::disk('public')->putFileAs('/uploads/'.$path, $file, $fileName);
    }
}
