<?php

/**
 * Created by Cristian.
 * Date: 05/09/16 11:27 PM.
 */

namespace Reliese\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Classify
{
    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public function annotation($name, $value)
    {
        return "\n * @$name $value";
    }

    /**
     * Constant template.
     *
     * @param string $name
     * @param mixed $value
     *
     * @param $prefix
     * @return string
     */
    public function constant($name, $value, $prefix = null)
    {
        $value = Dumper::export($value);
        return "\tpublic const $prefix$name = $value;\n";
    }

    /**
     * Field template.
     *
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function field($name, $value, $options = [])
    {
        $value = Dumper::export($value);
        $before = Arr::get($options, 'before', '');
        $visibility = Arr::get($options, 'visibility', 'protected');
        $after = Arr::get($options, 'after', "\n");

        return "$before\t$visibility \$$name = $value;$after";
    }

    /**
     * @param string $name
     * @param string $body
     * @param array $options
     *
     * @return string
     */
    public function method($name, $body, $options = [])
    {
        $visibility = Arr::get($options, 'visibility', 'public');
        $nameAndReturnType = "$name()";
        if(stripos($body, 'belongsTo') !== false){
            $nameAndReturnType .= ": "."\Illuminate\Database\Eloquent\Relations\BelongsTo";
        }
        if(stripos($body, 'hasOne') !== false){
            $nameAndReturnType .= ": "."\Illuminate\Database\Eloquent\Relations\HasOne";
        }
        if(stripos($body, 'hasMany') !== false){
            $nameAndReturnType .= ": "."\Illuminate\Database\Eloquent\Relations\HasMany";
        }
        return "\n\t$visibility function $nameAndReturnType\n\t{\n\t\t$body\n\t}\n";
    }

    public function mixin($class)
    {
        if (Str::startsWith($class, '\\')) {
            $class = Str::replaceFirst('\\', '', $class);
        }

        return "\tuse \\$class;\n";
    }
}
