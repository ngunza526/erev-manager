<script setup>
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ statements: Object });

const bilan = computed(() => props.statements.balance_sheet);
const resultat = computed(() => props.statements.income_statement);
const annexes = computed(() => props.statements.annexes);
const isBalanced = computed(() => Math.abs(bilan.value.control_gap) < 0.01);

const fmt = (value) => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const assetSections = [
  ['immobilisations', 'Immobilisations'],
  ['stocks_creances', 'Stocks et creances'],
  ['tresorerie', 'Tresorerie (banque, caisse, mobile money)'],
];
const liabilitySections = [
  ['fonds_propres', 'Fonds propres et reserves'],
  ['dettes_tiers', 'Dettes et tiers'],
];
const annexeSections = [
  ['cash_and_bank', 'Tresorerie disponible'],
  ['restricted_funds', 'Fonds affectes'],
  ['receivables', 'Creances membres'],
  ['payables', 'Dettes fournisseurs / organismes'],
];
</script>

<template>
  <AppLayout title="Etats financiers">
    <div class="grid">
      <section class="panel">
        <div class="report-head">
          <div>
            <h2>Etats financiers — Bilan et compte de resultat (OHADA)</h2>
            <small>Genere le {{ statements.generated_at }}</small>
          </div>
          <div class="tags no-print">
            <button class="btn secondary" type="button" @click="window.print()">Imprimer</button>
            <a class="btn secondary" href="/rapports/etats-ohada.pdf">Telecharger PDF</a>
            <a class="btn secondary" href="/rapports/etats-ohada.xlsx">Telecharger Excel</a>
          </div>
        </div>

        <h3>Bilan</h3>
        <div class="fs-columns">
          <div class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th colspan="2">Actif</th></tr></thead>
              <tbody>
                <template v-for="[key, label] in assetSections" :key="key">
                  <tr class="section-title"><td colspan="2">{{ label }}</td></tr>
                  <tr v-for="row in bilan.assets[key].rows" :key="row.code">
                    <td>{{ row.code }} — {{ row.label }}</td>
                    <td class="num">{{ fmt(row.amount) }}</td>
                  </tr>
                  <tr v-if="!bilan.assets[key].rows.length"><td colspan="2">—</td></tr>
                </template>
              </tbody>
              <tfoot>
                <tr><td>Total actif</td><td class="num">{{ fmt(bilan.assets_total) }}</td></tr>
              </tfoot>
            </table>
          </div>

          <div class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th colspan="2">Passif</th></tr></thead>
              <tbody>
                <template v-for="[key, label] in liabilitySections" :key="key">
                  <tr class="section-title"><td colspan="2">{{ label }}</td></tr>
                  <tr v-for="row in bilan.liabilities[key].rows" :key="row.code">
                    <td>{{ row.code }} — {{ row.label }}</td>
                    <td class="num">{{ fmt(row.amount) }}</td>
                  </tr>
                  <tr v-if="!bilan.liabilities[key].rows.length"><td colspan="2">—</td></tr>
                </template>
                <tr class="section-title"><td colspan="2">Resultat de l'exercice</td></tr>
                <tr><td>Resultat net (compte de resultat)</td><td class="num">{{ fmt(bilan.net_result) }}</td></tr>
              </tbody>
              <tfoot>
                <tr><td>Total passif</td><td class="num">{{ fmt(bilan.liabilities_total) }}</td></tr>
              </tfoot>
            </table>
          </div>
        </div>

        <p class="report-check" :class="{ 'is-off': !isBalanced }">
          {{ isBalanced
            ? `Bilan equilibre : actif (${fmt(bilan.assets_total)}) = passif (${fmt(bilan.liabilities_total)}).`
            : `Ecart de ${fmt(bilan.control_gap)} entre actif et passif — verifier les ecritures.` }}
        </p>

        <h3>Compte de resultat</h3>
        <div class="fs-columns">
          <div class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th colspan="2">Produits</th></tr></thead>
              <tbody>
                <tr v-for="row in resultat.revenues.rows" :key="row.code">
                  <td>{{ row.code }} — {{ row.label }}</td>
                  <td class="num">{{ fmt(row.amount) }}</td>
                </tr>
                <tr v-if="!resultat.revenues.rows.length"><td colspan="2">—</td></tr>
              </tbody>
              <tfoot>
                <tr><td>Total produits</td><td class="num">{{ fmt(resultat.revenues_total) }}</td></tr>
              </tfoot>
            </table>
          </div>

          <div class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th colspan="2">Charges</th></tr></thead>
              <tbody>
                <tr v-for="row in resultat.expenses.rows" :key="row.code">
                  <td>{{ row.code }} — {{ row.label }}</td>
                  <td class="num">{{ fmt(row.amount) }}</td>
                </tr>
                <tr v-if="!resultat.expenses.rows.length"><td colspan="2">—</td></tr>
              </tbody>
              <tfoot>
                <tr><td>Total charges</td><td class="num">{{ fmt(resultat.expenses_total) }}</td></tr>
              </tfoot>
            </table>
          </div>
        </div>
        <p class="report-check" :class="{ 'is-off': resultat.net_result < 0 }">
          Resultat net de l'exercice : {{ fmt(resultat.net_result) }} ({{ resultat.net_result >= 0 ? 'excedent' : 'deficit' }})
        </p>

        <h3>Annexes</h3>
        <div class="fs-columns fs-annexes">
          <div v-for="[key, label] in annexeSections" :key="key" class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th colspan="2">{{ label }}</th></tr></thead>
              <tbody>
                <tr v-for="row in annexes[key].rows" :key="row.code">
                  <td>{{ row.code }} — {{ row.label }}</td>
                  <td class="num">{{ fmt(row.amount) }}</td>
                </tr>
                <tr v-if="!annexes[key].rows.length"><td colspan="2">—</td></tr>
              </tbody>
              <tfoot>
                <tr><td>Total</td><td class="num">{{ fmt(annexes[key].total) }}</td></tr>
              </tfoot>
            </table>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
.fs-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin: 12px 0 20px;
}
.fs-annexes { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
@media (max-width: 860px) {
  .fs-columns { grid-template-columns: 1fr; }
}
</style>
