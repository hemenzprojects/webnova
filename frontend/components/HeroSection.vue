<template>
  <section class="relative min-h-[600px] md:min-h-[700px] overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0">
      <!-- Background Image -->
      <img
        src="https://20002096.fs1.hubspotusercontent-na1.net/hub/20002096/hubfs/Imported%20sitepage%20images/4882066.jpg?width=1920&height=1080&length=1920&upsize=true&upscale=true&name=4882066.jpg"
        alt="WEBNOVA Network Background"
        class="w-full h-full object-cover"
      />

      <!-- Dark gradient overlay to maintain readability -->
      <div class="absolute inset-0 bg-gradient-to-r from-primary/90 via-primary/70 to-primary/80"></div>

      <!-- Tech Circuit Board Pattern Overlay -->
      <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Cdefs%3E%3Cpattern id=%22grid%22 width=%22100%22 height=%22100%22 patternUnits=%22userSpaceOnUse%22%3E%3Cpath d=%22M 100 0 L 0 0 0 100%22 fill=%22none%22 stroke=%22%2300D9FF%22 stroke-width=%220.5%22 stroke-opacity=%220.1%22/%3E%3Ccircle cx=%220%22 cy=%220%22 r=%222%22 fill=%22%2300D9FF%22 opacity=%220.2%22/%3E%3Ccircle cx=%22100%22 cy=%220%22 r=%222%22 fill=%22%2300D9FF%22 opacity=%220.2%22/%3E%3Ccircle cx=%220%22 cy=%22100%22 r=%222%22 fill=%22%2300D9FF%22 opacity=%220.2%22/%3E%3Ccircle cx=%22100%22 cy=%22100%22 r=%222%22 fill=%22%2300D9FF%22 opacity=%220.2%22/%3E%3C/pattern%3E%3C/defs%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22url(%23grid)%22/%3E%3C/svg%3E'); background-size: 100px 100px; opacity: 0.3;"></div>
    </div>

    <!-- Diagonal Stripes Background (Left Side) -->
    <div class="absolute inset-y-0 left-0 w-1/3 bg-stripe-pattern opacity-30"></div>

    <!-- Animated Particles/Dots -->
    <div class="absolute inset-0 overflow-hidden">
      <div
        v-for="(particle, i) in particles"
        :key="i"
        class="absolute w-1 h-1 bg-accent rounded-full animate-float opacity-40"
        :style="{
          left: `${particle.left}%`,
          top: `${particle.top}%`,
          animationDelay: `${particle.delay}s`,
          animationDuration: `${particle.duration}s`
        }"
      ></div>
    </div>

    <!-- Glowing Light Beams -->
    <div class="absolute top-0 right-0 w-1/2 h-full">
      <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-accent opacity-10 rounded-full blur-3xl animate-glow"></div>
      <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-accent-light opacity-20 rounded-full blur-2xl animate-glow" style="animation-delay: 1s;"></div>
    </div>

    <!-- Content Container -->
    <div class="container mx-auto px-4 py-20 relative z-10">
      <div class="grid md:grid-cols-2 gap-12 items-center">

        <!-- Left Side: Text Content -->
        <div class="text-white">
          <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
            Connecting Ghana's Academic Excellence
          </h1>
          <p class="text-lg md:text-xl text-gray-200 mb-8 max-w-lg">
            Building world-class research and education infrastructure through
            high-speed internet, innovation, and a strong digital presence.
          </p>

          <!-- CTA Button -->
          <NuxtLink
            to="/about"
            class="inline-flex items-center gap-3 px-8 py-4 border-2 border-white rounded-full text-white hover:bg-white hover:text-primary transition-all duration-300 group"
          >
            <span class="font-semibold">More Information</span>
            <svg
              class="w-5 h-5 transform group-hover:translate-x-1 transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </NuxtLink>
        </div>

        <!-- Right Side: Hero Image -->
        <div class="relative flex justify-center items-center">
          <!-- Circular Frame for Image -->
          <div class="relative w-full max-w-md">
            <!-- Background Circle -->
            <div class="absolute inset-0 bg-gradient-to-br from-accent/30 to-primary-light/50 rounded-3xl blur-xl"></div>

            <!-- Image Container with Rounded Corners -->
            <div class="relative rounded-3xl overflow-hidden shadow-2xl">
              <!-- Hero Image -->
              <img
                src="https://20002096.fs1.hubspotusercontent-na1.net/hub/20002096/hubfs/Imported%20sitepage%20images/pexels-pixabay-256379.jpg?width=720&height=720&length=720&upsize=true&upscale=true&name=pexels-pixabay-256379.jpg"
                alt="WEBNOVA Network Globe"
                class="w-full h-auto object-cover"
              />
            </div>

            <!-- Overlapping Satellite/Tech Element -->
            <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-white rounded-full shadow-xl p-4 hidden md:block">
              <div class="w-full h-full bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center">
                <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Settings Icon (Bottom Right) -->
          <div class="absolute -bottom-4 -right-4 w-12 h-12 bg-primary-light rounded-full flex items-center justify-center shadow-lg cursor-pointer hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-white animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
interface Particle {
  left: number
  top: number
  delay: number
  duration: number
}

const particles = ref<Particle[]>([])

// Generate particles only on client-side to avoid hydration mismatch
onMounted(() => {
  particles.value = Array.from({ length: 30 }, () => ({
    left: Math.random() * 100,
    top: Math.random() * 100,
    delay: Math.random() * 6,
    duration: 4 + Math.random() * 4
  }))
})
</script>

<style scoped>
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}
</style>