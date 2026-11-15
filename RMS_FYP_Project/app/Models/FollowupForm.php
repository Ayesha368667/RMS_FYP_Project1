<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FollowupForm extends Model
{
    use HasFactory;

    // These questions are LOCKED. Admin cannot delete or change them.
    public const CORE_QUESTIONS = [
        ['key' => 'mood_score', 'type' => 'number', 'min' => 1, 'max' => 10, 'required' => true, 'locked' => true,
         'label_en' => 'How is your mood today? (1-10)',
         'label_ur' => 'آج آپ کا موڈ کیسا ہے؟ (1 سے 10)'],
        ['key' => 'pain_level', 'type' => 'number', 'min' => 0, 'max' => 10, 'required' => true, 'locked' => true,
         'label_en' => 'Pain level (0-10)',
         'label_ur' => 'درد کی شدت (0 سے 10)'],
        ['key' => 'craving_level', 'type' => 'number', 'min' => 0, 'max' => 10, 'required' => true, 'locked' => true,
         'label_en' => 'Craving level (0-10)',
         'label_ur' => 'طلب کی شدت (0 سے 10)'],
        ['key' => 'medicine_taken', 'type' => 'boolean', 'required' => true, 'locked' => true,
         'label_en' => 'Did you take your medicine on time?',
         'label_ur' => 'کیا آپ نے دوا وقت پر لی؟'],
        ['key' => 'relapse_reported', 'type' => 'boolean', 'required' => true, 'locked' => true,
         'label_en' => 'Have you relapsed since the last check-in?',
         'label_ur' => 'کیا پچھلے چیک اِن کے بعد ریلیپس (دوبارہ نشہ) ہوا؟'],
        ['key' => 'remarks', 'type' => 'textarea', 'required' => false, 'locked' => true,
         'label_en' => 'Anything else you would like to share?',
         'label_ur' => 'کوئی اور بات جو آپ بتانا چاہیں'],
    ];

    protected $fillable = [
        'form_name', 'description', 'version_number', 'questions', 'is_active', 'created_by',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function responses() { return $this->hasMany(FollowupResponse::class); }

    // Questions that are NOT core (the ones the admin added)
    public function extraQuestions(): array
    {
        $coreKeys = array_column(self::CORE_QUESTIONS, 'key');

        return collect($this->questions ?? [])
            ->reject(fn ($q) => in_array($q['key'] ?? null, $coreKeys, true))
            ->values()
            ->all();
    }

    // Core questions + the extra questions the admin typed
    public static function buildQuestions(array $extras): array
    {
        $list = [];

        foreach (array_values($extras) as $q) {
            $list[] = [
                'key'      => $q['key'] ?? 'extra_'.Str::lower(Str::random(6)),
                'type'     => $q['type'],
                'label_en' => $q['label_en'],
                'label_ur' => $q['label_ur'],
                'required' => (bool) ($q['required'] ?? false),
                'locked'   => false,
            ];
        }

        return array_merge(self::CORE_QUESTIONS, $list);
    }
}