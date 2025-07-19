<template>
  <div class="flex flex-col space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        Nova Flex Runner
      </h1>
      <div class="flex items-center space-x-3">
        <button
          @click="refreshCommands"
          :disabled="loading"
          class="btn btn-default"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Command Categories -->
    <div v-if="commands && Object.keys(commands).length > 0" class="space-y-6">
      <div
        v-for="(category, categoryKey) in commands"
        :key="categoryKey"
        class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700"
      >
        <!-- Category Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <svg v-if="category.icon" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(category.icon)" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ category.name }}
              </h3>
              <p v-if="category.description" class="text-sm text-gray-500 dark:text-gray-400">
                {{ category.description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Commands List -->
        <div class="p-6">
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="command in category.commands"
              :key="command.slug"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex flex-col space-y-3">
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ command.name }}
                  </h4>
                  <p v-if="command.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ command.description }}
                  </p>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ command.type }}
                  </span>
                  <button
                    @click="selectCommand(command, categoryKey)"
                    class="btn btn-primary btn-sm"
                  >
                    Run
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No commands configured</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Configure your commands in the nova-flex-runner.php config file.
      </p>
    </div>

    <!-- Loading State -->
    <div v-else class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <!-- Command Execution Modal -->
    <CommandExecutionModal
      v-if="selectedCommand"
      :command="selectedCommand"
      :category="selectedCategory"
      @close="closeModal"
      @executed="handleCommandExecuted"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import CommandExecutionModal from './CommandExecutionModal.vue'

export default {
  name: 'FlexRunner',
  components: {
    CommandExecutionModal
  },
  setup() {
    const commands = ref({})
    const loading = ref(true)
    const selectedCommand = ref(null)
    const selectedCategory = ref(null)

    const loadCommands = async () => {
      try {
        loading.value = true
        const response = await Nova.request().get('/nova-vendor/nova-flex-runner/api/commands')
        commands.value = response.data.commands
      } catch (error) {
        Nova.$toasted.error('Failed to load commands')
        console.error('Error loading commands:', error)
      } finally {
        loading.value = false
      }
    }

    const refreshCommands = () => {
      loadCommands()
    }

    const selectCommand = (command, category) => {
      selectedCommand.value = command
      selectedCategory.value = category
    }

    const closeModal = () => {
      selectedCommand.value = null
      selectedCategory.value = null
    }

    const handleCommandExecuted = (result) => {
      if (result.success) {
        Nova.$toasted.success('Command executed successfully')
      } else {
        Nova.$toasted.error('Command execution failed: ' + (result.error || 'Unknown error'))
      }
    }

    const getIconPath = (icon) => {
      const icons = {
        'play': 'M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M5 18l1.414-1.414M19 6.586l-1.414 1.414',
        'wrench-screwdriver': 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 11-4 0v1a1 1 0 01-1 1H4a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H4a1 1 0 01-1-1V6a1 1 0 011-1h3a1 1 0 001-1V4z',
        'circle-stack': 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'queue-list': 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
      }
      return icons[icon] || icons['play']
    }

    onMounted(() => {
      loadCommands()
    })

    return {
      commands,
      loading,
      selectedCommand,
      selectedCategory,
      refreshCommands,
      selectCommand,
      closeModal,
      handleCommandExecuted,
      getIconPath
    }
  }
}
</script>