const themeColor = (variable, fallback) =>
  `color-mix(in srgb, var(${variable}, ${fallback}) calc(<alpha-value> * 100%), transparent)`

module.exports = {
  content: [
    "./components/**/*.{js,vue,ts}",
    "./layouts/**/*.vue",
    "./pages/**/*.vue",
    "./plugins/**/*.{js,ts}",
    "./app.vue",
    "./themes/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Theme colours come from CSS variables set by the active theme and
        // Branding; color-mix keeps opacity modifiers like bg-primary/20 working
        primary: {
          DEFAULT: themeColor('--color-primary', '#0A1E3E'),
          light: themeColor('--color-primary-light', '#1A3A5C'),
          dark: themeColor('--color-primary-dark', '#050F1F'),
        },
        accent: {
          DEFAULT: themeColor('--color-accent', '#00D9FF'),
          light: themeColor('--color-accent-light', '#33E3FF'),
          dark: themeColor('--color-accent-dark', '#00A8CC'),
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