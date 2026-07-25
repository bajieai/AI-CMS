import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const primaryColor = ref('#1989fa')
  const darkMode = ref<'light' | 'dark' | 'auto'>('auto')
  const fontSize = ref<'small' | 'medium' | 'large'>('medium')

  function setTheme(config: any) {
    if (config.primary_color) primaryColor.value = config.primary_color
    if (config.dark_mode) darkMode.value = config.dark_mode
    if (config.font_size) fontSize.value = config.font_size
  }

  return { primaryColor, darkMode, fontSize, setTheme }
})
