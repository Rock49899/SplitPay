import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const userPermissions = ref([]);
const userRoles = ref([]);
const currentUser = ref(null);

export function usePermissions() {
  const router = useRouter();

  // Charger les permissions depuis localStorage
  const loadPermissions = () => {
    try {
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      userPermissions.value = userData.permissions || [];
      userRoles.value = userData.roles || [];
      currentUser.value = userData;
    } catch (error) {
      console.error('Error loading permissions:', error);
      userPermissions.value = [];
      userRoles.value = [];
      currentUser.value = null;
    }
  };

  // Sauvegarder les permissions
  const setPermissions = (permissions) => {
    userPermissions.value = permissions;
    
    // Mettre à jour le localStorage pour persister les permissions
    if (currentUser.value) {
      const updatedUser = {
        ...currentUser.value,
        permissions: permissions
      };
      localStorage.setItem('user', JSON.stringify(updatedUser));
    }
  };

  const setUser = (user) => {
    currentUser.value = user;
    if (user) {
      userPermissions.value = user.permissions || [];
      userRoles.value = user.roles || [];
      localStorage.setItem('user', JSON.stringify(user));
      
      console.log('[usePermissions] User updated:', {
        name: user.name,
        permissions: user.permissions?.length || 0,
        roles: user.roles?.map(r => r.label || r.code) || []
      });
    }
  };

  // Vérifier si l'utilisateur a une permission spécifique
  const hasPermission = (permission) => {
    if (!permission) return true; // Pas de permission requise
    return userPermissions.value.includes(permission);
  };

  // Vérifier si l'utilisateur a au moins une des permissions
  const hasAnyPermission = (permissions) => {
    if (!permissions || permissions.length === 0) return true;
    return permissions.some(perm => userPermissions.value.includes(perm));
  };

  // Vérifier si l'utilisateur a toutes les permissions
  const hasAllPermissions = (permissions) => {
    if (!permissions || permissions.length === 0) return true;
    return permissions.every(perm => userPermissions.value.includes(perm));
  };

  // Vérifier si l'utilisateur a un rôle spécifique
  const hasRole = (roleCode) => {
    return userRoles.value.some(role => role.code === roleCode);
  };

  // Vérifier si l'utilisateur a au moins un des rôles
  const hasAnyRole = (roleCodes) => {
    if (!roleCodes || roleCodes.length === 0) return true;
    return roleCodes.some(roleCode => hasRole(roleCode));
  };

  // Alias pour hasPermission
  const can = hasPermission;

  // Computed - est super admin institution
  const isSuperAdminInstitution = computed(() => hasRole('super_admin_institution'));

  // Computed - est admin plateforme
  const isPlatformAdmin = computed(() => {
    if (hasRole('platform_admin')) return true;
    return currentUser.value?.is_platform_admin === true || currentUser.value?.scope === 'platform';
  });
  
  // Computed - est super admin annexe
  const isSuperAdminAnnexe = computed(() => hasRole('super_admin_annexe'));
  
  // Computed - est gestionnaire
  const isGestionnaire = computed(() => hasRole('gestionnaire'));
  
  // Computed - est comptable
  const isComptable = computed(() => hasRole('comptable'));

  // Réinitialiser les permissions (lors de la déconnexion)
  const clearPermissions = () => {
    userPermissions.value = [];
    userRoles.value = [];
    currentUser.value = null;
    localStorage.removeItem('user');
  };

  // Charger les permissions au démarrage si pas encore chargées
  if (userPermissions.value.length === 0) {
    loadPermissions();
  }

  return {
    // State
    userPermissions,
    userRoles,
    currentUser,
    
    // Methods
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    can,
    setPermissions,
    setUser,
    loadPermissions,
    clearPermissions,
    
    // Computed
    isSuperAdminInstitution,
    isPlatformAdmin,
    isSuperAdminAnnexe,
    isGestionnaire,
    isComptable,
  };
}
