module.exports = {
  content: [
    "./components/**/*.{js,vue,ts}",
    "./layouts/**/*.vue",
    "./pages/**/*.vue",
    "./plugins/**/*.{js,ts}",
    "./app.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0A1E3E', // Deep navy blue
          light: '#1A3A5C',
          dark: '#050F1F',
        },
        accent: {
          DEFAULT: '#00D9FF', // Bright cyan
          light: '#33E3FF',
          dark: '#00A8CC',
        },
        secondary: '#059669', // Keep green for compatibility
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(135deg, #0A1E3E 0%, #1A4D6F 50%, #00A8CC 100%)',
        'stripe-pattern': 'repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.3) 10px, rgba(0,0,0,0.3) 20px)',
      },
      animation: {
        'float': 'float 6s ease-in-out infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-20px)' },
        },
        glow: {
          '0%': { opacity: '0.5' },
          '100%': { opacity: '1' },
        }
      }
    },
  },
  plugins: [],
}