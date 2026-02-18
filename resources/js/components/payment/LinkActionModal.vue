<template>
  <Modal @close="close">
    <template #body>
      <div class="p-4">
        <h3 class="text-lg font-semibold mb-2">Link actions</h3>
        <p class="text-sm text-gray-600 mb-3">Token: <code class="break-all">{{ link.token }}</code></p>
        <div class="space-y-3">
          <div>
            <button @click="copy" class="px-3 py-1 border rounded mr-2">Copy link</button>
            <button @click="openPublic" class="px-3 py-1 border rounded mr-2">Open public</button>
            <button @click="share" class="px-3 py-1 border rounded">Share</button>
          </div>

          <div>
            <label class="block text-sm text-gray-600">Send to email</label>
            <div class="flex gap-2 mt-1">
              <input v-model="email" type="email" class="flex-1 border rounded px-3 py-2" placeholder="email" />
              <button @click="sendEmail" class="px-3 py-1 bg-brand-500 text-white rounded">Send</button>
            </div>
          </div>

          <div>
            <button @click="disableLink" class="px-3 py-1 border rounded text-red-600">Disable link</button>
          </div>
        </div>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref } from 'vue';
import Modal from '@/components/payment/Modal.vue';
import paymentLinkService from '@/services/paymentLinkService';

const props = defineProps({
  link: { type: Object, required: true }
});
const emit = defineEmits(['close','changed']);

const email = ref(props.link.student?.email ?? '');

const close = () => emit('close');

const publicUrl = () => `${window.location.origin}/payment/${props.link.token}`;

const copy = async () => {
  try {
    await navigator.clipboard.writeText(publicUrl());
    alert('Link copied');
  } catch {
    alert('Copy failed');
  }
};

const openPublic = () => {
  window.open(publicUrl(), '_blank');
};

const share = async () => {
  if (navigator.share) {
    try {
      await navigator.share({ title: 'Payment link', text: 'Please pay', url: publicUrl() });
    } catch (e) { /* cancelled */ }
  } else {
    await copy();
  }
};

const sendEmail = async () => {
  try {
    const target = email.value;
    if (!target) return alert('Email required');
    await paymentLinkService.sendEmail(props.link.id, { email: target });
    alert('Email sent');
    emit('changed');
    close();
  } catch (e) {
    console.error('Send failed', e);
    alert(e.response?.data?.message || e.message || 'Send failed');
  }
};

const disableLink = async () => {
  try {
    // tentative: set status to 'disabled' (controller must accept)
    await paymentLinkService.update(props.link.id, { status: 'disabled' });
    alert('Link disabled');
    emit('changed');
    close();
  } catch (e) {
    console.error('Disable failed', e);
    alert(e.response?.data?.message || e.message || 'Disable failed');
  }
};
</script>

<style scoped></style>