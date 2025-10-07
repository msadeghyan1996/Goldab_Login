<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
abstract class BaseModel extends Model
{
    use SoftDeletes;
    protected $hidden = [
        'deleted_at',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        return parent::setAttribute($key, $value);
    }
    public function getTable()
    {
        return $this->table ?? str_replace('\\', '', snake_case(str_plural(class_basename($this))));
    }
}