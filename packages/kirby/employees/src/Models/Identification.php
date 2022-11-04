<?php

namespace Kirby\Employees\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Identification.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Identification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'type', 'name', 'code', 'expiration_date'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expiration_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Related employee.
     *
     * @return mixed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
