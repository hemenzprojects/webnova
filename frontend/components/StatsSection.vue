<template>
  <section class="relative py-20 overflow-hidden">
    <!-- Background with network pattern -->
    <div class="absolute inset-0 bg-hero-gradient">
      <!-- Animated particles (client-only to avoid hydration mismatch) -->
      <ClientOnly>
        <div class="absolute inset-0">
          <div
            v-for="i in 20"
            :key="i"
            class="absolute w-1 h-1 bg-accent rounded-full animate-float opacity-20"
            :style="{
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              animationDelay: `${Math.random() * 3}s`,
              animationDuration: `${3 + Math.random() * 3}s`
            }"
          ></div>
        </div>
      </ClientOnly>

      <!-- Light beams -->
      <div class="absolute inset-0 opacity-30">
        <div class="absolute top-0 left-1/4 w-px h-full bg-gradient-to-b from-transparent via-accent to-transparent"></div>
        <div class="absolute top-0 left-1/2 w-px h-full bg-gradient-to-b from-transparent via-accent-light to-transparent"></div>
        <div class="absolute top-0 left-3/4 w-px h-full bg-gradient-to-b from-transparent via-accent to-transparent"></div>
      </div>
    </div>

    <!-- Stats content -->
    <div class="container mx-auto px-4 relative z-10">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <!-- Stat 1 -->
        <div class="bg-primary/80 backdrop-blur-sm rounded-2xl p-8 text-center shadow-2xl border border-accent/20 hover:scale-105 transition-transform duration-300">
          <div class="text-5xl md:text-6xl font-bold text-white mb-2">
            <span class="counter" data-target="25">0</span><span class="text-accent">+</span>
          </div>
          <p class="text-gray-300 text-sm md:text-base">Member Institutions</p>
        </div>

        <!-- Stat 2 -->
        <div class="bg-primary/80 backdrop-blur-sm rounded-2xl p-8 text-center shadow-2xl border border-accent/20 hover:scale-105 transition-transform duration-300">
          <div class="text-5xl md:text-6xl font-bold text-white mb-2">
            <span class="counter" data-target="10">0</span><span class="text-accent">Gbps</span>
          </div>
          <p class="text-gray-300 text-sm md:text-base">Network Capacity</p>
        </div>

        <!-- Stat 3 -->
        <div class="bg-primary/80 backdrop-blur-sm rounded-2xl p-8 text-center shadow-2xl border border-accent/20 hover:scale-105 transition-transform duration-300">
          <div class="text-5xl md:text-6xl font-bold text-white mb-2">
            <span class="counter" data-target="99">0</span><span class="text-accent">%</span>
          </div>
          <p class="text-gray-300 text-sm md:text-base">Uptime Reliability</p>
        </div>

        <!-- Stat 4 -->
        <div class="bg-primary/80 backdrop-blur-sm rounded-2xl p-8 text-center shadow-2xl border border-accent/20 hover:scale-105 transition-transform duration-300">
          <div class="text-5xl md:text-6xl font-bold text-white mb-2">
            <span class="counter" data-target="150">0</span><span class="text-accent">K+</span>
          </div>
          <p class="text-gray-300 text-sm md:text-base">Students Connected</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // Animate counters
  const counters = document.querySelectorAll('.counter')

  counters.forEach(counter => {
    const target = parseInt(counter.getAttribute('data-target'))
    const duration = 2000 // 2 seconds
    const increment = target / (duration / 16) // 60fps
    let current = 0

    const updateCounter = () => {
      current += increment
      if (current < target) {
        counter.textContent = Math.floor(current)
        requestAnimationFrame(updateCounter)
      } else {
        counter.textContent = target
      }
    }

    // Start animation when element is in viewport
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          updateCounter()
          observer.unobserve(entry.target)
        }
      })
    })

    observer.observe(counter)
  })
})
</script>