/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2563EB',
          hover: '#1D4ED8'
        }
      },
      borderRadius: {
        sm: '6px',
        md: '10px',
        lg: '16px'
      }
    }
  },
  plugins: []
}
