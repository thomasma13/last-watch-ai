<?php

namespace App;

use App\Exceptions\AutomationException;
use Eloquent;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * DetectionProfile.
 *
 * @mixin Eloquent
 * @property int $id
 * @property string $name
 * @property string $token
 * @property string $chat_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|DetectionProfile[] $detectionProfiles
 * @property-read int|null $detection_profiles_count
 * @method static Builder|TelegramConfig newModelQuery()
 * @method static Builder|TelegramConfig newQuery()
 * @method static Builder|TelegramConfig query()
 * @method static Builder|TelegramConfig whereChatId($value)
 * @method static Builder|TelegramConfig whereCreatedAt($value)
 * @method static Builder|TelegramConfig whereId($value)
 * @method static Builder|TelegramConfig whereName($value)
 * @method static Builder|TelegramConfig whereToken($value)
 * @method static Builder|TelegramConfig whereUpdatedAt($value)
 * @property Carbon|null $deleted_at
 * @method static Builder|TelegramConfig onlyTrashed()
 * @method static Builder|TelegramConfig whereDeletedAt($value)
 * @method static Builder|TelegramConfig withTrashed()
 * @method static Builder|TelegramConfig withoutTrashed()
 */
class TelegramConfig extends Model implements AutomationConfigInterface
{
    use SoftDeletes;

    protected $fillable = ['name', 'token', 'chat_id'];

    public function detectionProfiles(): MorphToMany
    {
        return $this->morphToMany('App\DetectionProfile', 'automation_config');
    }

    /**
     * @param DetectionEvent $event
     * @param DetectionProfile $profile
     * @return bool
     * @throws AutomationException
     * @throws FileNotFoundException
     */
    public function run(DetectionEvent $event, DetectionProfile $profile): bool
    {
        $client = new TelegramClient($this->token, $this->chat_id);

        $path = Storage::path($event->imageFile->path);
        $imageExists = Storage::exists($event->imageFile->path);

        if (! $imageExists) {
            throw new FileNotFoundException('File not found at '.$path);
        }

        $success = $client->sendPhoto($path);

        if (! $success) {
            throw new AutomationException($client->getError());
        }

        return true;
    }

    protected static function booted()
    {
        static::deleted(function ($config) {
            $config->update(['name' => time().'::'.$config->name]);
        });
    }
}
