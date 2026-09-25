<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    Copy,
    LoaderCircle,
    Send,
    Sparkles,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

defineProps<{
    model: string;
}>();

const maxLength = 4000;

const prompt = ref('');
const answer = ref('');
const errorMessage = ref('');
const processing = ref(false);
const copied = ref(false);
const textarea = ref<HTMLTextAreaElement | null>(null);
const answerBlock = ref<HTMLElement | null>(null);

const canSubmit = computed(
    () => prompt.value.trim().length > 0 && !processing.value,
);

const examples = [
    'Объясни, что такое замыкание в JavaScript, простыми словами',
    'Составь SQL-запрос: топ-10 клиентов по сумме заказов за 2025 год',
    'Придумай 5 идей для пет-проекта на Laravel',
];

function csrfToken(): string {
    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function autoGrow(): void {
    const element = textarea.value;

    if (!element) {
        return;
    }

    element.style.height = 'auto';
    element.style.height = `${Math.min(element.scrollHeight, 320)}px`;
}

function useExample(value: string): void {
    prompt.value = value;
    nextTick(() => {
        autoGrow();
        textarea.value?.focus();
    });
}

async function submit(): Promise<void> {
    if (!canSubmit.value) {
        return;
    }

    processing.value = true;
    errorMessage.value = '';
    answer.value = '';
    copied.value = false;

    try {
        const response = await fetch('/prompt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ prompt: prompt.value }),
        });

        const data = await response.json().catch(() => null);

        if (!response.ok) {
            errorMessage.value =
                data?.message ??
                data?.errors?.prompt?.[0] ??
                `Не удалось получить ответ (ошибка ${response.status}).`;
            return;
        }

        answer.value = data?.answer ?? '';

        await nextTick();
        answerBlock.value?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    } catch {
        errorMessage.value =
            'Не удалось связаться с сервером. Проверьте соединение и повторите попытку.';
    } finally {
        processing.value = false;
    }
}

async function copyAnswer(): Promise<void> {
    try {
        await navigator.clipboard.writeText(answer.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        void submit();
    }
}
</script>

<template>
    <Head title="Главная" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex w-full max-w-3xl items-center justify-between px-6 py-4"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <Sparkles class="size-5 text-primary" />
                    <span>Промпты</span>
                </div>

                <nav class="flex items-center gap-2 text-sm">
                    <template v-if="$page.props.auth.user">
                        <Button as-child variant="outline" size="sm">
                            <Link :href="dashboard()">Панель</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="login()">Войти</Link>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="register()">Регистрация</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-6 py-12"
        >
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    О чём хотите спросить?
                </h1>
                <p class="text-sm text-muted-foreground">
                    Введите запрос и нажмите «Отправить». Модель:
                    <span class="font-medium text-foreground">{{ model }}</span>
                </p>
            </div>

            <form class="space-y-3" @submit.prevent="submit">
                <div
                    class="rounded-xl border border-border bg-card shadow-sm transition focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/30"
                >
                    <textarea
                        ref="textarea"
                        v-model="prompt"
                        :maxlength="maxLength"
                        rows="3"
                        placeholder="Например: объясни, как работает индексация в PostgreSQL…"
                        class="w-full resize-none bg-transparent px-4 py-3 text-sm outline-none placeholder:text-muted-foreground disabled:opacity-60"
                        :disabled="processing"
                        @input="autoGrow"
                        @keydown="handleKeydown"
                    />

                    <div
                        class="flex items-center justify-between gap-3 border-t border-border/60 px-3 py-2"
                    >
                        <span class="text-xs text-muted-foreground">
                            Ctrl / ⌘ + Enter — отправить
                        </span>

                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs tabular-nums"
                                :class="
                                    prompt.length > maxLength * 0.9
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ prompt.length }} / {{ maxLength }}
                            </span>

                            <Button
                                type="submit"
                                size="sm"
                                :disabled="!canSubmit"
                            >
                                <LoaderCircle
                                    v-if="processing"
                                    class="animate-spin"
                                />
                                <Send v-else />
                                {{ processing ? 'Генерация…' : 'Отправить' }}
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!prompt && !answer"
                    class="flex flex-wrap items-center gap-2"
                >
                    <span class="text-xs text-muted-foreground">
                        Попробуйте:
                    </span>
                    <button
                        v-for="example in examples"
                        :key="example"
                        type="button"
                        class="rounded-full border border-border px-3 py-1 text-xs text-muted-foreground transition hover:border-ring hover:text-foreground"
                        @click="useExample(example)"
                    >
                        {{ example }}
                    </button>
                </div>
            </form>

            <div
                v-if="errorMessage"
                class="flex items-start gap-3 rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
                role="alert"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0 text-destructive" />
                <p class="text-destructive">{{ errorMessage }}</p>
            </div>

            <section
                v-if="answer || processing"
                ref="answerBlock"
                class="space-y-3"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium text-muted-foreground">
                        Ответ
                    </h2>

                    <Button
                        v-if="answer"
                        variant="ghost"
                        size="sm"
                        @click="copyAnswer"
                    >
                        <Check v-if="copied" />
                        <Copy v-else />
                        {{ copied ? 'Скопировано' : 'Копировать' }}
                    </Button>
                </div>

                <div
                    class="rounded-xl border border-border bg-card p-4 text-sm leading-relaxed shadow-sm"
                >
                    <div
                        v-if="processing && !answer"
                        class="flex items-center gap-2 text-muted-foreground"
                    >
                        <LoaderCircle class="size-4 animate-spin" />
                        Модель думает…
                    </div>

                    <p v-else class="whitespace-pre-wrap">{{ answer }}</p>
                </div>
            </section>
        </main>
    </div>
</template>
