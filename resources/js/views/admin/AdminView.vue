<script setup lang="ts">
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'

import AdminAdsPanel from '@/views/admin/AdminAdsPanel.vue'
import AdminCategoriesPanel from '@/views/admin/AdminCategoriesPanel.vue'
import AdminReportsPanel from '@/views/admin/AdminReportsPanel.vue'
import AdminSettingsPanel from '@/views/admin/AdminSettingsPanel.vue'
</script>

<template>
  <section>
    <h1>Panel administratora</h1>

    <Tabs value="ads">
      <TabList>
        <Tab value="ads">
          Ogłoszenia
        </Tab>
        <Tab value="categories">
          Kategorie
        </Tab>
        <Tab value="reports">
          Zgłoszenia
        </Tab>
        <Tab value="settings">
          Ustawienia
        </Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="ads">
          <AdminAdsPanel />
        </TabPanel>
        <TabPanel value="categories">
          <AdminCategoriesPanel />
        </TabPanel>
        <TabPanel value="reports">
          <AdminReportsPanel />
        </TabPanel>
        <TabPanel value="settings">
          <AdminSettingsPanel />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </section>
</template>
