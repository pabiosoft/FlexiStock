/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./assets/**/*.js", "./templates/**/*.html.twig"],
  theme: {
    extend: {
      colors: {
        primary: "#0c0c0d",
        secondary: "#1BCF84",
        acent1: "#8C4AF2",
        acent2: "#6F55F6",
        acent3: "#1BCF84",
        acent4: "#E25F25",
        acent5: "#369FFF",
        gray: "#515A5A",
        sitebg: "#f8f8f8",
        bgLight: "rgba(164, 229, 224, 0.45)",
        softLight: "rgba(255, 255, 255, 0.6)",
        formBackground: "#f9fafb",
      },

      dropShadow: {
        xl: "0px 10px 20px 0px rgba(164, 229, 224, 0.15)",
      },
      container: {
        center: true,
        screens: {
          sm: "100%",
          md: "100%",
          lg: "100%",
          xl: "100%",
        },
      },
      fontSize: {
        sm: "12px",
        base: "14px",
        md: "16px",
        lg: "17px",
        xl: "18px",
        "2xl": "20px",
        "3xl": "22px",
        "4xl": "23px",
      },
    },
  },
  plugins: [require("@tailwindcss/forms")],
};
