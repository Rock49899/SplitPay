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
            <!-- <button @click="share" class="px-3 py-1 border rounded">Share</button> -->
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-2">Send to email</label>
            
            <div class="flex gap-2 mb-2">
              <input v-model="email" type="email" class="flex-1 border rounded px-3 py-2" placeholder="Email address" />
            </div>
            
            <div class="mb-2">
              <label class="block text-xs text-gray-500 mb-1">Message type</label>
              <select v-model="messageType" class="w-full border rounded px-3 py-2 text-sm">
                <option value="initial">📧 Initial notification</option>
                <option value="reminder">🔔 Friendly reminder</option>
                <option value="urgent">⚠️ Urgent reminder</option>
                <option value="final">⏰ Final notice</option>
              </select>
            </div>
            
            <div class="flex gap-2">
              <button @click="sendEmail" class="flex-1 px-3 py-2 bg-brand-500 text-white rounded hover:bg-brand-600 transition-colors font-medium">Send Email</button>
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
const messageType = ref('initial');

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
    await paymentLinkService.sendEmail(props.link.id, { 
      email: target,
      message_type: messageType.value 
    });
    alert('Email sent successfully!');
    emit('changed');
    close();
  } catch (e) {
    console.error('Send failed', e);
    alert(e.response?.data?.message || e.message || 'Send failed');
  }
};

const disableLink = async () => {
  try {
    console.log('🔴 === DISABLE LINK DEBUG START ===');
    console.log('🔴 Link ID:', props.link.id);
    console.log('🔴 Current status:', props.link.status);
    console.log('🔴 Payload:', { status: 'expired' });
    console.log('🔴 API Token present:', !!localStorage.getItem('api_token'));
    
    const response = await paymentLinkService.update(props.link.id, { status: 'expired' });
    
    console.log('✅ === DISABLE LINK SUCCESS ===');
    console.log('✅ Response status:', response.status);
    console.log('✅ Response data:', response.data);
    console.log('✅ New status:', response.data.status);
    
    alert('Link disabled successfully! Status: ' + response.data.status);
    emit('changed');
    close();
  } catch (e) {
    console.error('❌ === DISABLE LINK FAILED ===');
    console.error('❌ Error:', e);
    console.error('❌ Response status:', e.response?.status);
    console.error('❌ Response data:', e.response?.data);
    console.error('❌ Error message:', e.message);
    
    let errorMsg = 'Disable failed: ';
    if (e.response?.status === 403) {
      errorMsg += 'Permission denied. You need the \"link.view\" permission.';
    } else if (e.response?.status === 422) {
      errorMsg += 'Validation error: ' + JSON.stringify(e.response.data.errors || e.response.data.message);
    } else {
      errorMsg += e.response?.data?.message || e.message || 'Unknown error';
    }
    
    alert(errorMsg);
  }
};
</script>

<style scoped></style>