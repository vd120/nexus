<?php

namespace Database\Seeders;

use App\Models\PulsePrompt;
use Illuminate\Database\Seeder;

class PulsePromptSeeder extends Seeder
{
    public function run(): void
    {
        $prompts = [
            ['en' => 'What made you smile today?',                              'ar' => 'إيه اللي ضحّكك النهارده؟'],
            ['en' => 'One song you cannot stop playing this week?',             'ar' => 'إيه الأغنيه اللي مش قادر تبطل تسمعها الأسبوع ده؟'],
            ['en' => 'Best meal you had this month?',                           'ar' => 'أحسن أكله اتأكلتها الشهر ده؟'],
            ['en' => 'A small win from this week.',                             'ar' => 'انتصار صغير حصل معاك الأسبوع ده.'],
            ['en' => 'What is something new you learned recently?',             'ar' => 'إيه آخر حاجه جديده اتعلمتها؟'],
            ['en' => 'Where would you go right now if money was no issue?',     'ar' => 'لو الفلوس مش مشكله، كنت رحت فين دلوقتي؟'],
            ['en' => 'A book or show you would recommend right now.',           'ar' => 'كتاب أو مسلسل بتنصح بيه دلوقتي.'],
            ['en' => 'A habit you want to build this year.',                    'ar' => 'عاده عايز تبدأها السنه دي.'],
            ['en' => 'Something kind a stranger did for you.',                  'ar' => 'موقف لطيف عمله معاك حد ميعرفكش.'],
            ['en' => 'The last thing that made you laugh out loud.',            'ar' => 'آخر حاجه ضحّكتك بصوت عالي.'],
            ['en' => 'Coffee, tea, or something else?',                         'ar' => 'قهوه، شاي، ولا حاجه تانيه؟'],
            ['en' => 'What is one thing on your mind right now?',               'ar' => 'إيه أكتر حاجه في بالك دلوقتي؟'],
            ['en' => 'Describe your perfect Friday in 5 words.',                'ar' => 'وصف يوم جمعه مثالي في 5 كلمات.'],
            ['en' => 'Who inspires you and why?',                               'ar' => 'مين بيلهمك وليه؟'],
            ['en' => 'The best advice you ever received.',                      'ar' => 'أحسن نصيحه اتقالتلك.'],
            ['en' => 'What does your morning routine look like?',               'ar' => 'صباحك بيبدأ إزاي؟'],
            ['en' => 'A skill you wish you had.',                               'ar' => 'مهاره نفسك تتقنها.'],
            ['en' => 'What is your go-to comfort food?',                        'ar' => 'الأكله اللي بتلجأ ليها لما تكون متعب؟'],
            ['en' => 'A place that always feels like home.',                    'ar' => 'مكان بيحسسك إنك في بيتك.'],
            ['en' => 'What is one thing you cannot live without?',              'ar' => 'حاجه واحده مينفعش تعيش من غيرها.'],
            ['en' => 'A movie you have watched more than three times.',         'ar' => 'فيلم اتفرجت عليه أكتر من 3 مرات.'],
            ['en' => 'What is your biggest goal this month?',                   'ar' => 'إيه أكبر هدف ليك الشهر ده؟'],
            ['en' => 'Share a quote that stuck with you.',                      'ar' => 'اقتباس فضل عالق في دماغك.'],
            ['en' => 'One thing you are grateful for today.',                   'ar' => 'حاجه واحده ممتن بيها النهارده.'],
            ['en' => 'What was the highlight of your weekend?',                 'ar' => 'أحسن حاجه حصلت في الويك إند؟'],
            ['en' => 'What is a hobby you wish you had more time for?',        'ar' => 'هوايه نفسك يبقى عندك وقت ليها أكتر؟'],
            ['en' => 'Beach, mountains, or city?',                              'ar' => 'البحر، الجبل، ولا المدينه؟'],
            ['en' => 'A childhood memory that still makes you smile.',          'ar' => 'ذكرى من الطفوله لسه بتضحّكك.'],
            ['en' => 'The next thing you are looking forward to.',              'ar' => 'إيه أكتر حاجه مستنيها قدامك؟'],
            ['en' => 'If you could meet anyone for an hour, who?',              'ar' => 'لو هتقابل أي حد لساعه، تختار مين؟'],
        ];

        foreach ($prompts as $p) {
            PulsePrompt::updateOrCreate(
                ['question_en' => $p['en']],
                ['question_ar' => $p['ar'], 'is_active' => false]
            );
        }
    }
}
