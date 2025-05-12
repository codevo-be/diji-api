<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $fillable = ['metable_id', 'metable_type', 'key', 'value', 'type'];

    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['type'] = 'json';
            $this->attributes['value'] = json_encode($value);
        } elseif (is_int($value)) {
            $this->attributes['type'] = 'integer';
            $this->attributes['value'] = (string) $value;
        } elseif (is_float($value)) {
            $this->attributes['type'] = 'float';
            $this->attributes['value'] = (string) $value;
        } elseif (is_bool($value)) {
            $this->attributes['type'] = 'boolean';
            $this->attributes['value'] = $value ? '1' : '0';
        } else {
            $this->attributes['type'] = 'string';
            $this->attributes['value'] = $value;
        }
    }

    public static function findByKey(string $key)
    {
        return self::where('key', $key)->first();
    }

    public static function getValue(string $key)
    {
        $meta = self::where('key', $key)->first();
        return $meta ? $meta->value : null;
    }
}
