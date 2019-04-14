<?php
namespace llstarscreamll\Users\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\WorkShifts\Models\WorkShift;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class User extends Authenticatable
{
    use HasRoles, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Related identifications.
     *
     * @return mixed
     */
    public function identifications()
    {
        return $this->hasMany(Identification::class);
    }

    /**
     * Related work shifts.
     *
     * @return mixed
     */
    public function workShifts()
    {
        return $this->belongsToMany(WorkShift::class);
    }

    /**
     * @return mixed
     */
    public function timeClockLogs()
    {
        return $this->hasMany(TimeClockLog::class, 'employee_id');
    }

    /**
     * @param Carbon $time
     */
    public function getFirstWorkShiftByClosestTime(Carbon $time)
    {
        return $this->workShifts->first(function (WorkShift $workShift) use ($time) {
            [$hour, $seconds] = explode(':', $workShift->start_time);
            $start = now()->setTime($hour, $seconds)->subMinutes($workShift->grace_minutes_for_start_time);

            [$hour, $seconds] = explode(':', $workShift->end_time);
            $end = now()->setTime($hour, $seconds)->addMinutes($workShift->grace_minutes_for_end_time);

            return $time->between($start, $end);
        });
    }
}
