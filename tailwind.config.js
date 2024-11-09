/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        "form-border": "#D1D5DB",
        "form-focus": "#3B82F6",
        "form-background": "#F9FAFB",
        "form-error": "#F87171",
      },
      // Bordures arrondies
      borderRadius: {
        form: "0.375rem",
      },
      // Espacements personnalisés
      padding: {
        form: "0.75rem 1rem",
      },
      // Ombres personnalisées
      boxShadow: {
        "form-focus": "0 0 0 2px rgba(59, 130, 246, 0.5)",
      },
      // Transitions pour un effet focus plus doux
      transitionProperty: {
        form: 'border-color, box-shadow',
      },
      // Tailles de police pour les éléments de formulaire
      fontSize: {
        'form-label': '0.875rem', // Petite taille pour les labels
        'form-input': '1rem', // Taille de base pour les inputs
      },
    },
  },
  plugins: [],
}