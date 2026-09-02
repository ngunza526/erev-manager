<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({ title: String });

const page = usePage();
const pendingOffline = ref(0);

// La creation de communautes (provisioning de locataires) est reservee au
// role technique plateforme et n'apparait pas dans la navigation applicative.
const communityPrimaryNav = [
  { key: 'dashboard', href: '/', label: 'Dashboard', icon: 'D' },
  { key: 'eglises', href: '/eglises', label: 'Eglises', icon: 'E' },
  { key: 'utilisateurs', href: '/utilisateurs', label: 'Utilisateurs', icon: 'U' },
  { key: 'roles', href: '/roles-permissions', label: 'Roles', icon: 'R' },
];

const churchPrimaryNav = [
  { key: 'dashboard', href: '/', label: 'Dashboard', icon: 'D' },
  { key: 'membres', href: '/membres', label: 'Membres', icon: 'M' },
  { key: 'services', href: '/services', label: 'Cultes', icon: 'C' },
  { key: 'budgets', href: '/budgets', label: 'Budget', icon: 'B' },
  { key: 'communications', href: '/communications', label: 'Messages', icon: 'N' },
];

const communityNavSections = [
  {
    key: 'organisation',
    label: 'Organisation',
    items: [
      { href: '/eglises', label: 'Eglises' },
      { href: '/utilisateurs', label: 'Utilisateurs' },
      { href: '/roles-permissions', label: 'Roles et permissions' },
      { href: '/journal-audit', label: "Journal d'audit" },
    ],
  },
];

const churchNavSections = [
  {
    key: 'vie-eglise',
    label: 'Vie eglise',
    items: [
      { href: '/groupes', label: 'Groupes' },
      { href: '/evenements', label: 'Evenements' },
    ],
  },
  {
    key: 'pastorale',
    label: 'Pastorale',
    items: [
      { href: '/visiteurs', label: 'Visiteurs' },
      { href: '/convertis', label: 'Convertis' },
      { href: '/enfants', label: 'Enfants' },
      { href: '/volontaires', label: 'Volontaires' },
      { href: '/formations', label: 'Formations' },
      { href: '/sermons-media', label: 'Sermons' },
      { href: '/incidents', label: 'Securite' },
      { href: '/familles', label: 'Familles' },
      { href: '/discipolat', label: 'Discipolat' },
      { href: '/counseling', label: 'Counseling' },
      { href: '/evangelisation', label: 'Evangelisation' },
    ],
  },
  {
    key: 'finance',
    label: 'Finance',
    items: [
      { href: '/comptabilite', label: 'Comptabilite' },
      { href: '/contributions-publiques', label: 'Contributions publiques' },
      { href: '/depenses', label: 'Depenses' },
      { href: '/boutique-ressources', label: 'Boutique' },
      { href: '/fournisseurs', label: 'Fournisseurs' },
      { href: '/paie', label: 'Paie' },
      { href: '/rapprochements', label: 'Rapprochements' },
      { href: '/reversements', label: 'Reversements' },
      { href: '/fonds-dedies', label: 'Fonds dedies' },
      { href: '/mouvements-fonds', label: 'Mouvements' },
      { href: '/plan-comptable', label: 'Plan comptable' },
    ],
  },
  {
    key: 'engagement',
    label: 'Engagement',
    items: [
      { href: '/demandes-service', label: 'Demandes' },
      { href: '/reservations-locaux', label: 'Locaux' },
      { href: '/patrimoine', label: 'Patrimoine' },
      { href: '/conseils-reunions', label: 'Conseils' },
      { href: '/promesses-dons', label: 'Promesses' },
      { href: '/sondages', label: 'Sondages' },
      { href: '/temoignages', label: 'Temoignages' },
      { href: '/inscriptions-evenements', label: 'Tickets' },
    ],
  },
  {
    key: 'digital',
    label: 'Digital',
    items: [
      { href: '/qr-publics', label: 'QR publics' },
      { href: '/live-studio', label: 'Live studio' },
      { href: '/outils-ia', label: 'Outils IA' },
      { href: '/mediatheque', label: 'Mediatheque' },
      { href: '/solutions', label: 'Solutions' },
    ],
  },
];

const currentUrl = computed(() => page.url);
const userName = computed(() => page.props.auth?.user?.name ?? 'Equipe eglise');
const userSpace = computed(() => page.props.auth?.space ?? 'eglise');
const userLevel = computed(() => page.props.auth?.space_label ?? 'Eglise');
const contextSwitcher = computed(() => page.props.auth?.context_switcher ?? { can_switch: false });
const activeContextValue = computed(() => contextSwitcher.value.active_value ?? `${userSpace.value}:`);
const contextOptions = computed(() => {
  if (!contextSwitcher.value.can_switch) {
    return [];
  }

  const options = [];
  if (contextSwitcher.value.community) {
    options.push({
      value: `communaute:${contextSwitcher.value.community.id}`,
      label: `Coordination - ${contextSwitcher.value.community.designation}`,
    });
  }

  (contextSwitcher.value.churches ?? []).forEach((church) => {
    options.push({
      value: `eglise:${church.id}`,
      label: `Eglise - ${church.designation}`,
    });
  });

  return options;
});
const primaryNav = computed(() => userSpace.value === 'communaute' ? communityPrimaryNav : churchPrimaryNav);
const navSections = computed(() => userSpace.value === 'communaute' ? communityNavSections : churchNavSections);

const isActive = (href) => currentUrl.value === href || (href !== '/' && currentUrl.value.startsWith(href));
const sectionIsActive = (section) => section.items.some((item) => isActive(item.href));
const visibleContext = computed(() => {
  const active = [...primaryNav.value, ...navSections.value.flatMap((section) => section.items)].find((item) => isActive(item.href));
  return active?.label ?? 'Dashboard';
});

const logout = () => router.post('/logout');
const switchContext = (event) => {
  const [space, id] = event.target.value.split(':');
  router.post('/workspace/switch', {
    space,
    community_id: space === 'communaute' ? id : null,
    church_id: space === 'eglise' ? id : null,
  });
};
const syncOffline = async () => {
  if (window.ereveOffline) {
    await window.ereveOffline.flush();
    pendingOffline.value = await window.ereveOffline.pendingCount();
  }
};

onMounted(async () => {
  window.addEventListener('ereve-offline-count', (event) => {
    pendingOffline.value = event.detail.count;
  });
  if (window.ereveOffline) {
    pendingOffline.value = await window.ereveOffline.pendingCount();
  }
});
</script>

<template>
  <div class="shell">
    <aside class="side">
      <div class="brand">
        <span class="brand-mark">e</span>
        <span><strong>eReve Church</strong><small>Gestion d'eglise</small></span>
      </div>

      <label v-if="contextSwitcher.can_switch" class="context-switcher">
        <span>Espace actif</span>
        <select :value="activeContextValue" @change="switchContext">
          <option v-for="option in contextOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>

      <nav class="primary-nav" aria-label="Navigation principale">
        <Link
          v-for="item in primaryNav"
          :key="item.key"
          :href="item.href"
          :class="{ active: isActive(item.href) }"
        >
          <span class="nav-icon" aria-hidden="true">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </Link>
      </nav>

      <div class="nav-stack">
        <details
          v-for="section in navSections"
          :key="section.key"
          class="nav-section"
          :open="sectionIsActive(section)"
        >
          <summary>
            <span>{{ section.label }}</span>
            <span class="chevron">+</span>
          </summary>
          <div class="nav-links">
            <Link
              v-for="item in section.items"
              :key="item.href"
              :href="item.href"
              :class="{ active: isActive(item.href) }"
            >
              {{ item.label }}
            </Link>
          </div>
        </details>
      </div>

      <div class="side-actions">
        <button v-if="userSpace === 'eglise'" class="icon-btn" type="button" title="Synchroniser le hors-ligne" aria-label="Synchroniser le hors-ligne" @click="syncOffline">
          <span aria-hidden="true">S</span>
          <strong>{{ pendingOffline }}</strong>
        </button>
        <button class="icon-btn" type="button" title="Deconnexion" aria-label="Deconnexion" @click="logout">
          <span aria-hidden="true">Q</span>
        </button>
      </div>
    </aside>

    <main class="main">
      <div v-if="$page.props.flash?.success" class="flash">{{ $page.props.flash.success }}</div>
      <header class="top">
        <div>
          <p class="eyebrow">{{ visibleContext }}</p>
          <h1>{{ title }}</h1>
        </div>
        <div class="account-chip">
          <span>{{ userName }}</span>
          <small>{{ userLevel }}</small>
        </div>
      </header>
      <slot />
    </main>
  </div>
</template>
