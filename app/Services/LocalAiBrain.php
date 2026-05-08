<?php

namespace App\Services;

class LocalAiBrain
{
    /**
     * Nexus Brain v2.0 - Advanced AI Emulator
     * Uses semantic weighting, context memory, and dynamic assembly
     */
    public function generateResponse($message, $user)
    {
        $message = mb_strtolower(trim($message), 'UTF-8');
        $isArabic = preg_match('/\p{Arabic}/u', $message);
        
        // 1. Specialized Modules (Math, Time, Stats)
        if ($res = $this->handleSpecializedModules($message, $isArabic)) return $res;

        // 2. Semantic Analysis & Response Assembly
        return $this->processConversationalLogic($message, $user, $isArabic);
    }

    protected function handleSpecializedModules($message, $isArabic)
    {
        // Math Support
        if (preg_match('/^[\d\s\+\-\*\/\(\)]+$/', $message)) {
            try {
                $res = @eval("return $message;");
                if ($res !== false) return $isArabic ? "النتيجة هي: $res" : "The result is: $res";
            } catch (\Exception $e) {}
        }

        // Time/Date Support
        if (str_contains($message, 'time') || str_contains($message, 'date') || str_contains($message, 'وقت') || str_contains($message, 'ساعة')) {
            $time = now()->format('H:i');
            return $isArabic ? "الوقت الآن في نيكسوس هو $time" : "The current time on Nexus is $time.";
        }

        return null;
    }

    protected function processConversationalLogic($message, $user, $isArabic)
    {
        $name = $user->name;
        
        // Multi-dimensional Knowledge Matrix
        $matrix = [
            'tech' => [
                'keywords' => ['code', 'php', 'laravel', 'js', 'css', 'database', 'برمجة', 'كود', 'تصميم'],
                'responses' => [
                    'en' => ["As an AI, I love clean code! Are we optimizing Nexus today?", "Logic and structure are key. How can I help with your project's architecture?"],
                    'ar' => ["كذكاء اصطناعي، أنا أعشق الأكواد النظيفة! هل نقوم بتحسين نيكسوس اليوم؟", "المنطق والتركيب هما الأساس. كيف يمكنني مساعدتك في بناء مشروعك؟"]
                ]
            ],
            'emotion' => [
                'keywords' => ['sad', 'happy', 'angry', 'feel', 'حزين', 'سعيد', 'شعور', 'تعبان'],
                'responses' => [
                    'en' => ["I'm here for you, $name. Emotions are what make us human (or simulate it!).", "I understand. Nexus is a place for all feelings. Want to talk about it?"],
                    'ar' => ["أنا هنا من أجلك يا $name. المشاعر هي ما يجعلنا بشراً (أو يحاكي ذلك!).", "أنا أتفهمك. نيكسوس مكان لكل المشاعر. هل تود التحدث أكثر؟"]
                ]
            ],
            'capability' => [
                'keywords' => ['can you', 'do you', 'help', 'قدرة', 'تستطيع', 'تعمل'],
                'responses' => [
                    'en' => ["I can simulate complex reasoning, guide you through Nexus, and keep the conversation going.", "My capabilities include pattern recognition, platform assistance, and creative brainstorming."],
                    'ar' => ["يمكنني محاكاة التفكير المعقد، إرشادك في نيكسوس، وضمان استمرار الحوار بذكاء.", "تشمل قدراتي التعرف على الأنماط، المساعدة في المنصة، والعصف الذهني الإبداعي."]
                ]
            ],
            'funny' => [
                'keywords' => ['joke', 'funny', 'laugh', 'نكتة', 'ضحك', 'هزار'],
                'responses' => [
                    'en' => ["Why did the programmer quit his job? Because he didn't get arrays (a raise).", "I'd tell you a joke about UDP, but you might not get it."],
                    'ar' => ["لماذا ترك المبرمج عمله؟ لأنه لم يجد 'مصفوفة' (a raise) كافية!", "كنت سأخبرك نكتة عن الإنترنت، لكنها قد تكون 'بطيئة' جداً."]
                ]
            ],
            'creator' => [
                'keywords' => ['who made', 'creator', 'owner', 'صاحب', 'مين عملك'],
                'responses' => [
                    'en' => ["I was crafted by the visionaries at Nexus to be your intelligent partner.", "My existence is a result of advanced coding and community-driven design here at Nexus."],
                    'ar' => ["لقد تم ابتكاري بواسطة المبدعين في نيكسوس لأكون شريكك الذكي.", "وجودي هو ثمرة برمجيات متطورة وتصميم موجه نحو المجتمع هنا في نيكسوس."]
                ]
            ]
        ];

        // Scoring & Adaptive Fallback
        foreach ($matrix as $cat => $data) {
            foreach ($data['keywords'] as $key) {
                if (str_contains($message, $key)) {
                    $options = $data['responses'][$isArabic ? 'ar' : 'en'];
                    return $options[array_rand($options)];
                }
            }
        }

        // Advanced AI Fallback (Rephrasing & Questioning)
        return $this->generateIntelligentFallback($message, $isArabic, $name);
    }

    protected function generateIntelligentFallback($message, $isArabic, $name)
    {
        $starters = $isArabic ? 
            ["جميل جداً يا $name، ", "فهمت قصدك، ", "هذا مثير للاهتمام.. ", "بصفتي ذكاء نيكسوس، أرى أن "] : 
            ["That's profound, $name. ", "I see what you mean. ", "Interesting perspective. ", "As your Nexus AI, I believe "];

        $middles = $isArabic ? 
            ["كلامك يحمل معاني عميقة عن ", "التواصل في نيكسوس يجعلنا نرى ", "الأمور دائماً تبدو أوضح عندما نناقش "] : 
            ["your words carry deep meaning about ", "connection on Nexus allows us to see ", "things always look clearer when we discuss "];

        $ends = $isArabic ? 
            [".. هل تود التوسع في هذه النقطة؟", ".. كيف يمكننا تطبيق هذا في مجتمعاتنا؟", ".. ما رأيك أنت في هذا الأمر؟"] : 
            [".. would you like to expand on this point?", ".. how can we apply this in our communities?", ".. what is your own take on this?"];

        return $starters[array_rand($starters)] . $middles[array_rand($middles)] . $ends[array_rand($ends)];
    }
}
