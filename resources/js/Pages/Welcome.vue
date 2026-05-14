<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { motion, useScroll, useTransform } from 'motion-v';
import { ref } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const manifestoRef = ref(null);
const { scrollYProgress } = useScroll({
    target: manifestoRef,
    offset: ["start end", "end start"]
});

const opacity1 = useTransform(scrollYProgress, [0.1, 0.2], [0, 1]);
const opacity2 = useTransform(scrollYProgress, [0.3, 0.4], [0, 1]);
const opacity3 = useTransform(scrollYProgress, [0.5, 0.6], [0, 1]);

const y1 = useTransform(scrollYProgress, [0.1, 0.2], [20, 0]);
const y2 = useTransform(scrollYProgress, [0.3, 0.4], [20, 0]);
const y3 = useTransform(scrollYProgress, [0.5, 0.6], [20, 0]);

// Mock interaction states
const postContent = ref('');
const isTyping = ref(false);
const chatMessages = ref([
    { id: 1, text: "Hey! Did you see the new privacy update?", sender: 'other' },
    { id: 2, text: "Just read it. End-to-end intent is a game changer.", sender: 'me' }
]);

const simulateTyping = () => {
    isTyping.value = true;
    setTimeout(() => {
        isTyping.value = false;
        chatMessages.value.push({ id: 3, text: "Exactly. No one else can read this.", sender: 'other' });
    }, 2000);
};
</script>

<template>
    <Head title="Nexus - Private Social Revolution" />

    <div class="min-h-screen bg-[#ffffff] text-[#09090b] font-sans selection:bg-[#3b82f6]/20 overflow-x-hidden">
        <!-- Navigation -->
        <nav class="fixed top-0 w-full z-[100] backdrop-blur-xl bg-white/60 border-b border-[#e4e4e7]/50">
            <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
                <div class="flex items-center gap-2 group cursor-pointer">
                    <div class="w-7 h-7 bg-[#09090b] rounded-[6px] flex items-center justify-center transition-transform group-hover:scale-105">
                        <span class="text-white font-bold text-sm tracking-tighter">N</span>
                    </div>
                    <span class="text-lg font-semibold tracking-tight">Nexus</span>
                </div>

                <div class="hidden md:flex items-center gap-8 text-[13px] font-medium text-[#71717a]">
                    <a href="#manifesto" class="hover:text-black transition-colors">Manifesto</a>
                    <a href="#features" class="hover:text-black transition-colors">Features</a>
                    <a href="#technology" class="hover:text-black transition-colors">Stack</a>
                </div>

                <div v-if="canLogin" class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="text-[13px] font-medium hover:text-[#3b82f6] transition-colors"
                    >
                        Go to Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-[13px] font-medium hover:text-[#3b82f6] transition-colors"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-4 py-1.5 bg-[#09090b] text-white text-[13px] font-medium rounded-full hover:bg-[#18181b] transition-all shadow-sm active:scale-95"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <section class="relative pt-40 pb-24 px-6">
                <div class="max-w-5xl mx-auto text-center">
                    <motion
                        initial={{ opacity: 0, y: 30, filter: 'blur(10px)' }}
                        animate={{ opacity: 1, y: 0, filter: 'blur(0px)' }}
                        transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
                    >
                        <h1 class="text-6xl md:text-8xl lg:text-9xl font-bold tracking-tight leading-[0.9] mb-10">
                            Social Media,<br />
                            <span class="text-[#71717a]/30">without the surveillance.</span>
                        </h1>
                        <p class="max-w-xl mx-auto text-lg md:text-xl text-[#71717a] font-medium leading-tight mb-12">
                            A premium, real-time social layer built on absolute privacy and user autonomy.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                :href="route('register')"
                                class="w-full sm:w-auto px-8 py-3 bg-[#09090b] text-white font-semibold rounded-full hover:bg-[#18181b] transition-all shadow-xl shadow-black/10 text-base"
                            >
                                Join the Revolution
                            </Link>
                            <button
                                @click="simulateTyping"
                                class="w-full sm:w-auto px-8 py-3 bg-white border border-[#e4e4e7] font-semibold rounded-full hover:bg-[#fafafa] transition-all text-base"
                            >
                                See how it works
                            </button>
                        </div>
                    </motion>
                </div>
            </section>

            <!-- Privacy Manifesto -->
            <section id="manifesto" ref="manifestoRef" class="py-40 px-6 bg-[#fafafa]/50 border-y border-[#e4e4e7]/30">
                <div class="max-w-4xl mx-auto space-y-32">
                    <motion :style="{ opacity: opacity1, y: y1 }" class="space-y-6">
                        <span class="text-[12px] font-bold tracking-widest uppercase text-[#3b82f6]">Zero Tracking</span>
                        <h2 class="text-4xl md:text-6xl font-bold tracking-tight leading-none">Your activity is your business.</h2>
                        <p class="text-xl text-[#71717a] leading-relaxed max-w-2xl">
                            We don't track your location, your interests, or your keystrokes. Nexus is a blind engine—we provide the space, you provide the life.
                        </p>
                    </motion>

                    <motion :style="{ opacity: opacity2, y: y2 }" class="space-y-6">
                        <span class="text-[12px] font-bold tracking-widest uppercase text-[#3b82f6]">No Algorithms</span>
                        <h2 class="text-4xl md:text-6xl font-bold tracking-tight leading-none">Connection over compulsion.</h2>
                        <p class="text-xl text-[#71717a] leading-relaxed max-w-2xl">
                            There is no engagement engine. Your feed is chronological, your notifications are real-time, and your attention is your own.
                        </p>
                    </motion>

                    <motion :style="{ opacity: opacity3, y: y3 }" class="space-y-6">
                        <span class="text-[12px] font-bold tracking-widest uppercase text-[#3b82f6]">Data Ownership</span>
                        <h2 class="text-4xl md:text-6xl font-bold tracking-tight leading-none">You own the graph.</h2>
                        <p class="text-xl text-[#71717a] leading-relaxed max-w-2xl">
                            Export your entire history or delete your existence with one click. We don't hold your data hostage; we just keep it safe until you decide otherwise.
                        </p>
                    </motion>
                </div>
            </section>

            <!-- Feature Bento Grid (Full Info) -->
            <section id="features" class="py-40 px-6">
                <div class="max-w-7xl mx-auto">
                    <div class="mb-20">
                        <h2 class="text-4xl font-bold tracking-tight mb-4">Elite Capabilities</h2>
                        <p class="text-[#71717a] font-medium">Full transparency on the Nexus toolkit.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-[300px]">
                        <!-- Showstopper 1: Interactive Post Creator -->
                        <div class="md:col-span-8 md:row-span-2 bg-[#fafafa] rounded-[40px] border border-[#e4e4e7]/50 p-10 overflow-hidden relative group">
                            <div class="relative z-10 max-w-sm">
                                <h3 class="text-3xl font-bold mb-4">High-Fidelity Feed</h3>
                                <p class="text-[#71717a] font-medium leading-relaxed mb-8">
                                    Share without compromise. Up to 30 media files per post, 50MB per file. Full resolution, full control.
                                </p>
                            </div>
                            
                            <!-- Interactive Mock -->
                            <div class="absolute -right-10 -bottom-10 w-full max-w-md bg-white rounded-3xl shadow-2xl border border-[#e4e4e7] p-6 transition-transform group-hover:scale-[1.02] group-hover:-translate-y-2 duration-700 ease-out-expo">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 animate-pulse"></div>
                                    <div class="space-y-1">
                                        <div class="w-20 h-3 bg-gray-100 rounded animate-pulse"></div>
                                        <div class="w-12 h-2 bg-gray-50 rounded animate-pulse"></div>
                                    </div>
                                </div>
                                <textarea 
                                    v-model="postContent"
                                    placeholder="What's on your mind?"
                                    class="w-full h-32 p-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-0 resize-none transition-all placeholder:text-gray-300"
                                ></textarea>
                                <div class="mt-6 flex items-center justify-between">
                                    <div class="flex gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 cursor-pointer hover:bg-blue-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500 cursor-pointer hover:bg-purple-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        </div>
                                    </div>
                                    <button class="px-6 py-2 bg-[#09090b] text-white text-xs font-bold rounded-full opacity-50 cursor-not-allowed">Post</button>
                                </div>
                            </div>
                        </div>

                        <!-- Showstopper 2: Real-time Chat -->
                        <div class="md:col-span-4 md:row-span-2 bg-[#fafafa] rounded-[40px] border border-[#e4e4e7]/50 p-10 overflow-hidden relative group">
                            <h3 class="text-2xl font-bold mb-4">Real-time Presence</h3>
                            <p class="text-[#71717a] text-sm font-medium leading-relaxed mb-8">
                                True instant messaging via Socket.IO. Typing indicators, read receipts, and delivery confirmations out of the box.
                            </p>
                            
                            <!-- Interactive Mock -->
                            <div class="space-y-3">
                                <div v-for="msg in chatMessages" :key="msg.id" 
                                    :class="[
                                        'max-w-[85%] p-3 text-xs rounded-2xl transition-all duration-500',
                                        msg.sender === 'me' ? 'ml-auto bg-[#3b82f6] text-white rounded-tr-none' : 'bg-white border border-[#e4e4e7] rounded-tl-none'
                                    ]"
                                >
                                    {{ msg.text }}
                                </div>
                                <div v-if="isTyping" class="flex gap-1 p-2">
                                    <div class="w-1 h-1 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div class="w-1 h-1 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                                    <div class="w-1 h-1 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Tiles -->
                        <div class="md:col-span-4 bg-[#fafafa] rounded-[40px] border border-[#e4e4e7]/50 p-8 group">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-[#e4e4e7] mb-6 transition-transform group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Ephemeral Stories</h4>
                            <p class="text-sm text-[#71717a] leading-relaxed">Moments that last 24 hours. Full view tracking and emoji reactions built in.</p>
                        </div>

                        <div class="md:col-span-4 bg-[#fafafa] rounded-[40px] border border-[#e4e4e7]/50 p-8 group">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-[#e4e4e7] mb-6 transition-transform group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <h4 class="text-xl font-bold mb-2">AI Support</h4>
                            <p class="text-sm text-[#71717a] leading-relaxed">A context-aware AI assistant to help you find content and navigate the platform.</p>
                        </div>

                        <div class="md:col-span-4 bg-[#fafafa] rounded-[40px] border border-[#e4e4e7]/50 p-8 group">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-[#e4e4e7] mb-6 transition-transform group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Infinite Groups</h4>
                            <p class="text-sm text-[#71717a] leading-relaxed">Create public communities or invite-only circles with unique access links.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Technology Stack -->
            <section id="technology" class="py-40 px-6 bg-[#09090b] text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-24 items-center">
                        <div>
                            <h2 class="text-4xl md:text-6xl font-bold tracking-tight mb-8">Built on the Edge.</h2>
                            <p class="text-xl text-gray-400 leading-relaxed mb-12">
                                Nexus isn't just a design statement; it's a technical feat. Built with the modern web's most powerful primitives for unmatched speed and security.
                            </p>
                            <div class="grid grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <div class="text-2xl font-bold">Laravel 12</div>
                                    <div class="text-xs font-bold uppercase tracking-widest text-[#3b82f6]">Backend Engine</div>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-2xl font-bold">Vue 3</div>
                                    <div class="text-xs font-bold uppercase tracking-widest text-[#3b82f6]">Reactive Core</div>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-2xl font-bold">Socket.IO</div>
                                    <div class="text-xs font-bold uppercase tracking-widest text-[#3b82f6]">Real-time layer</div>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-2xl font-bold">Sanctum</div>
                                    <div class="text-xs font-bold uppercase tracking-widest text-[#3b82f6]">Auth security</div>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <div class="aspect-video bg-white/5 rounded-[40px] border border-white/10 p-1 flex items-center justify-center overflow-hidden">
                                <div class="w-full h-full bg-[#09090b] rounded-[36px] border border-white/5 flex items-center justify-center">
                                    <div class="text-center space-y-4">
                                        <div class="text-[60px] font-bold tracking-tighter animate-pulse">99.9%</div>
                                        <div class="text-sm font-bold uppercase tracking-widest text-gray-500">Uptime Reliability</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final CTA -->
            <section class="py-40 px-6 text-center">
                <div class="max-w-3xl mx-auto">
                    <h2 class="text-5xl md:text-7xl font-bold tracking-tight mb-10">Start your Private Era.</h2>
                    <Link
                        :href="route('register')"
                        class="inline-block px-12 py-5 bg-[#09090b] text-white font-bold rounded-full hover:bg-[#18181b] transition-all transform hover:scale-105 active:scale-95 text-xl shadow-2xl shadow-black/20"
                    >
                        Create your Nexus
                    </Link>
                </div>
            </section>
        </main>

        <footer class="py-20 px-6 border-t border-[#e4e4e7] bg-white text-[#71717a] text-[13px]">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-10">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-[#09090b] rounded-[4px] flex items-center justify-center text-[10px] text-white font-bold">N</div>
                    <span class="font-semibold text-black">Nexus</span>
                </div>
                <div class="flex gap-10 font-medium">
                    <a href="#" class="hover:text-black">Privacy</a>
                    <a href="#" class="hover:text-black">Terms</a>
                    <a href="#" class="hover:text-black">Security</a>
                    <a href="#" class="hover:text-black">Contact</a>
                </div>
                <div class="font-medium">&copy; {{ new Date().getFullYear() }} Nexus Social Lab.</div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap');

.font-sans {
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.ease-out-expo {
    transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
}

::-webkit-scrollbar {
    width: 0px;
}

html {
    scroll-behavior: smooth;
}
</style>
