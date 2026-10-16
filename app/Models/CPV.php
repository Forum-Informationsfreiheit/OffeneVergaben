<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CPV
 *
 * @property string $code
 * @property string|null $trimmed_code
 * @property int $control_digit
 * @property string $name
 * @property string|null $name_en
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dataset> $datasets
 * @property-read int|null $datasets_count
 * @property-read mixed $level
 * @method static \Illuminate\Database\Eloquent\Builder|CPV newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CPV newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CPV query()
 * @method static \Illuminate\Database\Eloquent\Builder|CPV whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CPV whereControlDigit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CPV whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CPV whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CPV whereTrimmedCode($value)
 * @mixin \Eloquent
 */
class CPV extends Model
{
    const STR_CODE_LENGTH = 8;   // all cpv codes are length 8

    protected $table = 'cpvs';

    protected $appends = [ 'level' ];

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $timestamps = false;

    public static function zeroFill($value) {
        return str_pad($value,self::STR_CODE_LENGTH,'0',STR_PAD_RIGHT);
    }

    public static function trim($value) {

    }

    public function toString() {
        return $this->code . ' ' . $this->name;
    }

    public function datasets() {
        return $this->belongsToMany('App\Models\Dataset','cpv_dataset','cpv_code','dataset_id');
    }

    public function getLevelAttribute() {

        $temp = rtrim($this->code,'0');
        $len  = strlen($temp);

        // level 1 does not make sense, level 2 is the lowest it can get

        return $len === 1 ? 2 : $len;
    }
}
