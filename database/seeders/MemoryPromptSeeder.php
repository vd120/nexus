<?php

namespace Database\Seeders;

use App\Models\PulsePrompt;
use Illuminate\Database\Seeder;

/**
 * Seeds 50 deep memory-evoking prompts that drive the weekly Memory Prompt.
 * Egyptian Arabic dialect to match the existing daily seeder's tone.
 *
 * Run independently or via DatabaseSeeder; uses updateOrCreate keyed on
 * question_en so re-running is safe.
 */
class MemoryPromptSeeder extends Seeder
{
    public function run(): void
    {
        $prompts = [
            // Childhood & Family
            ['en' => 'Describe a sound from your childhood.',                          'ar' => 'وصف صوت من أيام طفولتك.'],
            ['en' => 'The meal that means home.',                                      'ar' => 'الأكله اللي معناها البيت.'],
            ['en' => 'The smell of your grandmother\'s house.',                        'ar' => 'ريحه بيت ستك.'],
            ['en' => 'The first room you remember.',                                   'ar' => 'أول أوضه فاكرها في حياتك.'],
            ['en' => 'Your mother\'s hands.',                                          'ar' => 'إيدين أمك.'],
            ['en' => 'Words your father said often.',                                  'ar' => 'كلام كان أبوك بيقوله كتير.'],
            ['en' => 'Your favorite hiding spot as a kid.',                            'ar' => 'أحسن مكان كنت بتختبي فيه وانت صغير.'],
            ['en' => 'A meal someone made you when you were sick.',                    'ar' => 'أكله حد عملهالك وانت عيان.'],
            ['en' => 'What home smelled like.',                                        'ar' => 'البيت كان ريحته إيه.'],
            ['en' => 'The first time you missed home.',                                'ar' => 'أول مره وحشك البيت.'],

            // Firsts & Growing Up
            ['en' => 'The first time you cooked for someone.',                         'ar' => 'أول مره طبخت فيها لحد.'],
            ['en' => 'The first time you felt grown.',                                 'ar' => 'أول مره حسيت إنك كبرت.'],
            ['en' => 'Your first heartbreak.',                                         'ar' => 'أول مره قلبك انكسر.'],
            ['en' => 'The first friend you made.',                                     'ar' => 'أول صاحب عملته في حياتك.'],
            ['en' => 'The first time you stayed up all night.',                        'ar' => 'أول مره سهرت الليل كله.'],
            ['en' => 'Your first job.',                                                'ar' => 'أول شغل شتغلته.'],
            ['en' => 'The first time you lived alone.',                                'ar' => 'أول مره تعيش لوحدك.'],
            ['en' => 'The first time you traveled alone.',                             'ar' => 'أول مره تسافر لوحدك.'],
            ['en' => 'Your first apartment.',                                          'ar' => 'أول شقه سكنتها.'],
            ['en' => 'The first time you failed at something.',                        'ar' => 'أول مره فشلت في حاجه.'],

            // Relationships & Connections
            ['en' => 'A song that brings someone back.',                               'ar' => 'أغنيه بترجعلك حد.'],
            ['en' => 'A laugh you can still hear.',                                    'ar' => 'ضحكه لسه سامعها في ودنك.'],
            ['en' => 'A friend who became family.',                                    'ar' => 'صاحب بقى زي العيله.'],
            ['en' => 'Hands that comforted you.',                                      'ar' => 'إيدين هدّتك.'],
            ['en' => 'Someone who believed in you.',                                   'ar' => 'حد آمن بيك.'],
            ['en' => 'A teacher who shaped you.',                                      'ar' => 'مدرس أو مدرسه أثّروا فيك.'],
            ['en' => 'A friendship that ended.',                                       'ar' => 'صداقه خلصت.'],
            ['en' => 'Someone you wish you\'d thanked.',                               'ar' => 'حد كنت نفسك تشكره.'],
            ['en' => 'The person who taught you to love.',                             'ar' => 'الشخص اللي علمك الحب.'],
            ['en' => 'A conversation that changed everything.',                        'ar' => 'كلام غيّر كل حاجه.'],

            // Moments & Turning Points
            ['en' => 'The day everything changed.',                                    'ar' => 'اليوم اللي كل حاجه اتغيرت فيه.'],
            ['en' => 'A moment of unexpected kindness.',                               'ar' => 'موقف طيبه جالك من غير ما تتوقع.'],
            ['en' => 'Something you wish you\'d said.',                                'ar' => 'حاجه نفسك كنت قلتها.'],
            ['en' => 'A lesson from someone who\'s gone.',                             'ar' => 'درس اتعلمته من حد مبقاش معانا.'],
            ['en' => 'A summer that lasted.',                                          'ar' => 'صيف فضل عالق في دماغك.'],
            ['en' => 'A door you can still see.',                                      'ar' => 'باب لسه شايفه قدامك.'],
            ['en' => 'A neighborhood you loved.',                                      'ar' => 'حي كنت بتحبه.'],
            ['en' => 'A road trip you remember.',                                      'ar' => 'رحله بالعربيه لسه فاكرها.'],
            ['en' => 'A song from a wedding.',                                         'ar' => 'أغنيه من فرح.'],
            ['en' => 'The moment you knew you had to leave.',                          'ar' => 'اللحظه اللي عرفت فيها إنك لازم تمشي.'],

            // Reflections & Wisdom
            ['en' => 'A risk that paid off.',                                          'ar' => 'مخاطره نجحت.'],
            ['en' => 'A mistake you\'re grateful for.',                                'ar' => 'غلطه شاكر إنك عملتها.'],
            ['en' => 'A place that felt like magic.',                                  'ar' => 'مكان حسيته سحر.'],
            ['en' => 'The moment you felt most alive.',                                'ar' => 'اللحظه اللي حسيت فيها إنك عايش فعلاً.'],
            ['en' => 'A fear you overcame.',                                           'ar' => 'خوف اتغلبت عليه.'],
            ['en' => 'A moment of pure joy.',                                          'ar' => 'لحظه فرح صافي.'],
            ['en' => 'The hardest thing you\'ve done.',                                'ar' => 'أصعب حاجه عملتها.'],
            ['en' => 'The best advice you ever got.',                                  'ar' => 'أحسن نصيحه اتقالتلك.'],
            ['en' => 'A moment you wish you could relive.',                            'ar' => 'لحظه نفسك تعيشها تاني.'],
            ['en' => 'What you want to be remembered for.',                            'ar' => 'إيه اللي نفسك الناس تفتكرك بيه.'],
        ];

        foreach ($prompts as $p) {
            PulsePrompt::updateOrCreate(
                ['question_en' => $p['en']],
                [
                    'question_ar' => $p['ar'],
                    'type'        => 'memory',
                    'is_active'   => false,
                ]
            );
        }
    }
}
