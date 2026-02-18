import api from './api';

const index = (params = {}) => api.get('admin/students', { params });
const show = (id) => api.get(`admin/students/${id}`);
const store = (payload) => api.post('admin/students', payload);
//  PATCH pour update partiel, fallback sur PUT si nécessaire
const update = async (id, payload) => {
  try {
    return await api.patch(`admin/students/${id}`, payload);
  } catch (err) {
    const status = err?.response?.status;
    if (status === 405 || status === 404) {
      return await api.put(`admin/students/${id}`, payload);
    }
    throw err;
  }
};
const destroy = (id) => api.delete(`admin/students/${id}`);

// endpoint pour créer un lien de paiement
const createPaymentLink = (id, payload) => api.post(`admin/students/${id}/payment-link`, payload);

const studentService = {
  index,
  show,
  store,
  update,
  destroy,
  createPaymentLink,

  // récupérer les infos financières : essayer l'endpoint dédié, sinon fallback calculé à partir du student
  async financials(id) {
    try {
      const res = await api.get(`admin/students/${id}/financials`);
      return res;
    } catch (err) {
      const status = err?.response?.status;
      if (status === 404) {
        const sRes = await show(id);
        const s = sRes.data?.student ?? sRes.data ?? {};
        // rassembler les paiements depuis différentes structures possibles
        let payments = [];
        if (Array.isArray(s.payments) && s.payments.length) payments = s.payments;
        else if (Array.isArray(s.payments?.data) && s.payments.data.length) payments = s.payments.data;
        else if (Array.isArray(s.paymentLinks) && s.paymentLinks.length) {
          // paymentLinks -> installments -> payments
          payments = s.paymentLinks.flatMap(pl => {
            if (!Array.isArray(pl.installments)) return [];
            return pl.installments.flatMap(inst => inst.payments ?? []);
          }).filter(Boolean);
        }
        else if (Array.isArray(s.installments)) {
          payments = s.installments.flatMap(inst => inst.payments ?? []).filter(Boolean);
        }
        const amountPaid = payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
        const tuition = Number(s.tuition_amount ?? s.tuition ?? 0) || 0;
        const amountDue = Math.max(0, tuition - amountPaid);
        const sortedPayments = [...payments].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        const lastPayment = sortedPayments[0] ?? null;
        // format similaire à un endpoint REST : { data: { data: { ... } } }
        return {
          data: {
            data: {
              tuition_amount: tuition,
              amount_paid: amountPaid,
              amount_due: amountDue,
              last_payment_date: lastPayment ? (lastPayment.created_at ?? lastPayment.date ?? null) : null,
              recent_payments: sortedPayments.slice(0, 10),
            }
          },
          status: 200
        };
      }
      // pour les autres erreurs, remonter l'erreur
      throw err;
    }
  },
};

export default studentService
