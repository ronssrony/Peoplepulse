<script setup lang="ts">
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Camera, X, User as UserIcon } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    profile_picture: null as File | null,
});

const previewUrl = ref<string | null>(null);

const currentProfilePicture = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (user.value.profile_picture) return `/storage/${user.value.profile_picture}`;
    return null;
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.profile_picture = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removePreview = () => {
    form.profile_picture = null;
    previewUrl.value = null;
    const input = document.getElementById('profile_picture') as HTMLInputElement;
    if (input) input.value = '';
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post('/settings/profile', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            previewUrl.value = null;
            form.profile_picture = null;
        },
    });
};

const removeProfilePicture = () => {
    if (confirm('Are you sure you want to remove your profile picture?')) {
        form.delete('/settings/profile/picture', {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your profile picture, name and email address"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Profile Picture Section -->
                    <div class="grid gap-2">
                        <Label>Profile Picture</Label>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div 
                                    class="h-24 w-24 rounded-full overflow-hidden bg-muted flex items-center justify-center border-2 border-dashed border-muted-foreground/25"
                                >
                                    <img 
                                        v-if="currentProfilePicture" 
                                        :src="currentProfilePicture" 
                                        alt="Profile picture"
                                        class="h-full w-full object-cover"
                                    />
                                    <UserIcon v-else class="h-12 w-12 text-muted-foreground" />
                                </div>
                                <label 
                                    for="profile_picture"
                                    class="absolute bottom-0 right-0 p-1.5 bg-primary text-primary-foreground rounded-full cursor-pointer hover:bg-primary/90 transition-colors"
                                >
                                    <Camera class="h-4 w-4" />
                                </label>
                            </div>
                            <div class="flex flex-col gap-2">
                                <input
                                    id="profile_picture"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <div class="flex gap-2">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        size="sm"
                                        @click="($refs.fileInput as HTMLInputElement)?.click()"
                                        as="label"
                                        for="profile_picture"
                                        class="cursor-pointer"
                                    >
                                        Choose Photo
                                    </Button>
                                    <Button 
                                        v-if="previewUrl" 
                                        type="button" 
                                        variant="ghost" 
                                        size="sm"
                                        @click="removePreview"
                                    >
                                        <X class="h-4 w-4 mr-1" />
                                        Cancel
                                    </Button>
                                    <Button 
                                        v-else-if="user.profile_picture" 
                                        type="button" 
                                        variant="ghost" 
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="removeProfilePicture"
                                    >
                                        <X class="h-4 w-4 mr-1" />
                                        Remove
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    JPG, PNG, GIF or WebP. Max 2MB.
                                </p>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.profile_picture" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                            readonly
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="form.processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
