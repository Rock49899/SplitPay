import { ref, computed } from 'vue'
import { usePermissions } from './usePermissions'
import api from '@/services/api'

// État global partagé
const currentUser = ref(null)
const userAnnexes = ref([]) // Liste des annexes de l'utilisateur avec leurs rôles
const activeAnnexeId = ref(null)
const permissionsByAnnexe = ref({}) // Permissions groupées par annexe ID

export function useAnnexeContext() {
  const { setPermissions, setUser } = usePermissions()

  // Annexe actuellement active
  const activeAnnexe = computed(() => {
    return userAnnexes.value.find(a => a.id === activeAnnexeId.value) || userAnnexes.value[0] || null
  })

  // Rôle actif pour l'annexe courante
  const activeRole = computed(() => {
    if (!currentUser.value || !activeAnnexeId.value) return null
    
    // Chercher le rôle correspondant à l'annexe active
    const roleForAnnexe = currentUser.value.roles?.find(r => 
      r.annexe_id === activeAnnexeId.value
    )
    
    return roleForAnnexe || currentUser.value.roles?.[0] || null
  })

  // Permissions actives pour l'annexe courante UNIQUEMENT
  const activePermissions = computed(() => {
    if (!activeAnnexeId.value) return []
    return permissionsByAnnexe.value[activeAnnexeId.value] || []
  })

  /**
   * Applique les permissions de l'annexe active
   */
  const applyAnnexePermissions = (annexeId) => {
    const permissions = permissionsByAnnexe.value[annexeId] || []
    const permissionCodes = permissions.map(p => p.code || p)
    
    // Trouver le rôle pour cette annexe
    const annexe = userAnnexes.value.find(a => a.id === annexeId)
    const roleForAnnexe = annexe?.role ? [annexe.role] : []
    
    console.log('[AnnexeContext] Applying permissions for annexe:', annexeId)
    console.log('[AnnexeContext] Role:', roleForAnnexe)
    console.log('[AnnexeContext] Permissions:', permissionCodes)
    
    // Mettre à jour les permissions et sauvegarder dans localStorage
    setPermissions(permissionCodes)
    
    // Mettre à jour l'utilisateur avec le bon rôle et les bonnes permissions
    if (currentUser.value) {
      const updatedUser = {
        ...currentUser.value,
        permissions: permissionCodes,
        roles: roleForAnnexe,
        active_annexe_id: annexeId
      }
      localStorage.setItem('user', JSON.stringify(updatedUser))
    }
    
    // Sauvegarder l'état complet des permissions par annexe
    localStorage.setItem('permissions_by_annexe', JSON.stringify(permissionsByAnnexe.value))
  }

  /**
   * Initialiser le contexte avec les données utilisateur
   */
  const initializeContext = (userData) => {
    currentUser.value = userData
    
    // Extraire les permissions par annexe
    if (userData.permissions_by_annexe) {
      permissionsByAnnexe.value = userData.permissions_by_annexe
    } else {
      // Récupérer depuis localStorage si disponible (pour éviter la perte au rechargement)
      try {
        const saved = localStorage.getItem('permissions_by_annexe')
        if (saved) {
          permissionsByAnnexe.value = JSON.parse(saved)
        }
      } catch (e) {
        console.warn('[AnnexeContext] Failed to load permissions from localStorage:', e)
      }
    }
    
    // Extraire la liste des annexes
    if (userData.annexes && Array.isArray(userData.annexes)) {
      userAnnexes.value = userData.annexes.map(annexe => {
        // Trouver le rôle associé à cette annexe
        const roleForAnnexe = userData.roles?.find(r => r.annexe_id === annexe.id)
        
        return {
          id: annexe.id,
          name: annexe.name,
          is_principal: annexe.is_principal,
          role: roleForAnnexe ? {
            code: roleForAnnexe.code,
            label: roleForAnnexe.label
          } : null
        }
      })
    }
    
    // Charger l'annexe active depuis localStorage ou utiliser la principale
    const savedAnnexeId = localStorage.getItem('active_annexe_id')
    if (savedAnnexeId && userAnnexes.value.find(a => a.id === savedAnnexeId)) {
      activeAnnexeId.value = savedAnnexeId
    } else {
      // Utiliser l'annexe principale ou la première
      const principal = userAnnexes.value.find(a => a.is_principal)
      activeAnnexeId.value = principal?.id || userAnnexes.value[0]?.id || null
    }
    
    // Appliquer les permissions de l'annexe active
    if (activeAnnexeId.value) {
      applyAnnexePermissions(activeAnnexeId.value)
    }
    
    // Mettre à jour l'utilisateur dans usePermissions
    // (appelé APRÈS applyAnnexePermissions pour ne pas écraser les permissions)
    setUser({
      ...userData,
      permissions: permissionsByAnnexe.value[activeAnnexeId.value]?.map(p => p.code || p) || [],
      roles: userAnnexes.value.find(a => a.id === activeAnnexeId.value)?.role ? 
             [userAnnexes.value.find(a => a.id === activeAnnexeId.value).role] : [],
      active_annexe_id: activeAnnexeId.value
    })
  }

  /**
   * Changer l'annexe active
   */
  const switchAnnexe = async (annexeId) => {
    const annexe = userAnnexes.value.find(a => a.id === annexeId)
    if (!annexe) {
      console.warn('[AnnexeContext] Annexe non trouvée:', annexeId)
      return false
    }
    
    try {
      // Recharger les permissions pour cette annexe depuis l'API
      const response = await api.get(`/admin/me/annexe/${annexeId}`)
      const data = response.data
      
      // Mettre à jour les permissions pour cette annexe
      if (data.permissions) {
        permissionsByAnnexe.value[annexeId] = data.permissions.map(code => ({ code }))
      }
      
      // Changer l'annexe active
      activeAnnexeId.value = annexeId
      localStorage.setItem('active_annexe_id', annexeId)
      
      // Appliquer les nouvelles permissions
      applyAnnexePermissions(annexeId)
      
      // Émettre un événement pour notifier les composants
      window.dispatchEvent(new CustomEvent('annexe-switched', { 
        detail: { annexeId, annexe, permissions: data.permissions } 
      }))
      
      // Forcer la mise à jour des composants réactifs
      window.dispatchEvent(new CustomEvent('permissions-updated', {
        detail: { permissions: data.permissions, annexeId }
      }))
      
      console.log('[AnnexeContext] Switched to annexe:', annexe.name, 'Role:', annexe.role?.label)
      console.log('[AnnexeContext] New permissions:', data.permissions)
      
      return true
    } catch (error) {
      console.error('[AnnexeContext] Failed to switch annexe:', error)
      return false
    }
  }

  /**
   * Réinitialiser le contexte (déconnexion)
   */
  const resetContext = () => {
    currentUser.value = null
    userAnnexes.value = []
    activeAnnexeId.value = null
    permissionsByAnnexe.value = {}
    localStorage.removeItem('active_annexe_id')
    localStorage.removeItem('permissions_by_annexe')
  }

  return {
    // État
    currentUser,
    userAnnexes,
    activeAnnexeId,
    permissionsByAnnexe,
    
    // Computed
    activeAnnexe,
    activeRole,
    activePermissions,
    
    // Méthodes
    initializeContext,
    switchAnnexe,
    resetContext,
    applyAnnexePermissions,
  }
}
