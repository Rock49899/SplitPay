/**
 * Tailwind configuration to ensure production builds scan all templates
 * and keep dynamic classes used by Vue/Blade/JS.
 */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './resources/**/*.php',
  ],
  // Safelist common dynamic class prefixes that are generated at runtime
  safelist: [
    { pattern: /^(bg|text|border|hover:bg|hover:border|focus:border|md:|lg:|p|m|px|py|w|h|max-w|leading|text|rounded|gap|grid|col|flex|items|justify)-/ },
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
