/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "../**/*.{php,html,js}",
    "../admin/**/*.{php,html,js}",
    "../guard/**/*.{php,html,js}",
    "../homeowners/**/*.{php,html,js}",
    "../assets/**/*.{php,html,js}",
    "../auth/**/*.{php,html,js}",
    "../includes/**/*.{php,html,js}",
    "../api/**/*.{php,html,js}",
  ],
  theme: {
    extend: {
      colors: {
        // Salt & Pepper Theme
        'salt': {
          white: '#FAFAFA',
          light: '#F5F5F5',
          gray: '#E5E7EB',
        },
        'pepper': {
          charcoal: '#1F2937',
          dark: '#111827',
          slate: '#374151',
        },
        primary: '#6B7280',
        'primary-dark': '#4B5563',
        secondary: '#374151',
        success: '#16A34A',
        danger: '#DC2626',
        warning: '#F59E0B',
        sidebar: 'rgba(31, 41, 55, 0.95)',
      },
      backdropBlur: {
        'sidebar': '14px',
      },
      animation: {
        'fadeIn': 'fadeIn 0.4s ease forwards',
        'spin': 'spin 1s linear infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        }
      }
    },
  },
  plugins: [],
}
