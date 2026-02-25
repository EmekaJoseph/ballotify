<template>
  <div class="main-wrapper">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold text-primary" href="#">
          <div class="brand-icon me-2 d-flex align-items-center justify-content-center bg-primary text-white rounded">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          Ballotify
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto fw-medium">
            <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
            <li class="nav-item"><a class="nav-link" href="#">How it Works</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
          </ul>
          <div class="d-flex gap-2">
            <button class="btn btn-link text-decoration-none text-dark fw-medium">Login</button>
            <button class="btn btn-primary px-4 fw-bold">Get Started</button>
          </div>
        </div>
      </div>
    </nav>

    <main>
      <!-- Hero Section -->
      <section class="hero-section py-5 overflow-hidden">
        <div class="container py-lg-5">
          <div class="row align-items-center gy-5">
            <div class="col-lg-7 text-center text-lg-start">
              <h1 class="display-3 fw-bolder lh-sm mb-4">
                Modern Voting, <br />
                <span class="text-primary-gradient">Perfectly Simplified.</span>
              </h1>
              <p class="lead text-muted mb-5 mx-auto mx-lg-0 max-width-600">
                Create, manage, and scale your elections with Ballotify. Secure, transparent, and user-friendly voting
                for organizations of all sizes.
              </p>
              <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                <button class="btn btn-primary btn-lg px-5 fw-bold shadow">Create Your first Event</button>
                <button class="btn btn-outline-dark btn-lg px-4">View Demo</button>
              </div>
              <div class="mt-5 d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                <div class="avatar-group d-flex">
                  <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
                    class="avatar border border-white border-2 rounded-circle" alt="User">
                  <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Anita"
                    class="avatar border border-white border-2 rounded-circle ms-n3" alt="User">
                  <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Brian"
                    class="avatar border border-white border-2 rounded-circle ms-n3" alt="User">
                </div>
                <p class="small mb-0 font-weight-bold">
                  <span class="text-primary fw-bold">2,000+</span> organizations trust Ballotify
                </p>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="hero-img-container position-relative">
                <div class="hero-blob bg-primary opacity-10 rounded-circle blur-3xl position-absolute"></div>
                <img src="/hero_illustration.png" alt="Ballotify Hero"
                  class="img-fluid position-relative z-1 animate-float drop-shadow">
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Features Section -->
      <section class="features-section bg-light py-5">
        <div class="container py-5">
          <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Everything you need to <br class="d-none d-md-block"> run a successful
              election</h2>
            <p class="lead text-muted mx-auto max-width-700">
              From small community polls to large-scale organizational elections, we've got you covered with premium
              features.
            </p>
          </div>
          <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 border-0 shadow-sm transition-hover">
                <div class="card-body p-4">
                  <div
                    class="feature-icon bg-primary bg-opacity-10 text-primary rounded-3 mb-4 d-flex align-items-center justify-content-center">
                    <i class="bi bi-shield-lock-fill fs-3"></i>
                  </div>
                  <h4 class="fw-bold mb-3">Secure Voting</h4>
                  <p class="text-muted">End-to-end encryption and blockchain verification ensure every vote is
                    tamper-proof and authentic.</p>
                </div>
              </div>
            </div>
            <!-- Feature 2 -->
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 border-0 shadow-sm transition-hover">
                <div class="card-body p-4">
                  <div
                    class="feature-icon bg-success bg-opacity-10 text-success rounded-3 mb-4 d-flex align-items-center justify-content-center">
                    <i class="bi bi-link-45deg fs-3"></i>
                  </div>
                  <h4 class="fw-bold mb-3">Unique Voting Links</h4>
                  <p class="text-muted">Generate secure, one-time use links for your voters, ensuring each person votes
                    only once.</p>
                </div>
              </div>
            </div>
            <!-- Feature 3 -->
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 border-0 shadow-sm transition-hover">
                <div class="card-body p-4">
                  <div
                    class="feature-icon bg-info bg-opacity-10 text-info rounded-3 mb-4 d-flex align-items-center justify-content-center">
                    <i class="bi bi-bar-chart-fill fs-3"></i>
                  </div>
                  <h4 class="fw-bold mb-3">Real-time Analytics</h4>
                  <p class="text-muted">Monitor voter turnout and results in real-time with beautiful, interactive
                    dashboards.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- How It Works -->
      <section class="py-5">
        <div class="container py-5">
          <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Simple 4-Step Process</h2>
          </div>
          <div class="row g-4 text-center">
            <div class="col-sm-6 col-lg-3">
              <div
                class="step-number bg-primary text-white rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center fs-2 fw-bold">
                1</div>
              <h5 class="fw-bold">Create Event</h5>
              <p class="text-muted small px-3">Set up your election with titles, dates, and instructions.</p>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div
                class="step-number bg-primary text-white rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center fs-2 fw-bold">
                2</div>
              <h5 class="fw-bold">Add Candidates</h5>
              <p class="text-muted small px-3">Define categories and add candidates with photos and profiles.</p>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div
                class="step-number bg-primary text-white rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center fs-2 fw-bold">
                3</div>
              <h5 class="fw-bold">Import Voters</h5>
              <p class="text-muted small px-3">Upload your voter list. We'll generate unique codes for everyone.</p>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div
                class="step-number bg-primary text-white rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center fs-2 fw-bold">
                4</div>
              <h5 class="fw-bold">Launch & Vote</h5>
              <p class="text-muted small px-3">Send unique links. Voters enter their code and cast their ballot.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="py-5 bg-primary text-white">
        <div class="container py-5 text-center">
          <h2 class="display-4 fw-bold mb-4">Ready to host your next fair election?</h2>
          <p class="lead opacity-75 mb-5 mx-auto max-width-700">
            Join thousands of organizations using Ballotify to make democratic decisions with confidence.
          </p>
          <button class="btn btn-light btn-lg px-5 fw-bold">Create Your Event Now</button>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
      <div class="container">
        <div class="row g-4 mb-4">
          <div class="col-lg-3">
            <a class="navbar-brand d-flex align-items-center fw-bold text-white mb-4" href="#">
              <div
                class="brand-icon me-2 d-flex align-items-center justify-content-center bg-primary text-white rounded">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              Ballotify
            </a>
            <p class="small opacity-75">Making modern voting reliable and simple for everyone, everywhere.</p>
            <div class="d-flex gap-3 mt-4">
              <a href="#" class="text-white opacity-75"><i class="bi bi-twitter-x"></i></a>
              <a href="#" class="text-white opacity-75"><i class="bi bi-facebook"></i></a>
              <a href="#" class="text-white opacity-75"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
          <div class="col-sm-6 col-md-3 col-lg-2 offset-lg-1">
            <h6 class="fw-bold mb-4">Services</h6>
            <ul class="list-unstyled opacity-75 small space-y-2">
              <li><a href="#" class="text-white text-decoration-none">Event Creation</a></li>
              <li><a href="#" class="text-white text-decoration-none">Voter Management</a></li>
              <li><a href="#" class="text-white text-decoration-none">Real-time Results</a></li>
              <li><a href="#" class="text-white text-decoration-none">Consultancy</a></li>
            </ul>
          </div>
          <div class="col-sm-6 col-md-3 col-lg-2">
            <h6 class="fw-bold mb-4">Company</h6>
            <ul class="list-unstyled opacity-75 small space-y-2">
              <li><a href="#" class="text-white text-decoration-none">About us</a></li>
              <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
              <li><a href="#" class="text-white text-decoration-none">Jobs</a></li>
              <li><a href="#" class="text-white text-decoration-none">Press kit</a></li>
            </ul>
          </div>
          <div class="col-sm-6 col-md-3 col-lg-2">
            <h6 class="fw-bold mb-4">Legal</h6>
            <ul class="list-unstyled opacity-75 small space-y-2">
              <li><a href="#" class="text-white text-decoration-none">Terms of use</a></li>
              <li><a href="#" class="text-white text-decoration-none">Privacy policy</a></li>
              <li><a href="#" class="text-white text-decoration-none">Cookie policy</a></li>
            </ul>
          </div>
        </div>
        <hr class="opacity-10">
        <div class="row pt-2">
          <div class="col text-center">
            <p class="small opacity-50 mb-0">&copy; 2026 Ballotify Ltd. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
/* Custom modern styles for Bootstrap layout */
:root {
  --primary-gradient: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);
}

.text-primary-gradient {
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-icon {
  width: 40px;
  height: 40px;
}

.avatar {
  width: 40px;
  height: 40px;
}

.ms-n3 {
  margin-left: -0.75rem !important;
}

.max-width-600 {
  max-width: 600px;
}

.max-width-700 {
  max-width: 700px;
}

.hero-blob {
  width: 400px;
  height: 400px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  filter: blur(80px);
}

.feature-icon {
  width: 64px;
  height: 64px;
}

.step-number {
  width: 70px;
  height: 70px;
}

.transition-hover {
  transition: all 0.3s ease;
}

.transition-hover:hover {
  transform: translateY(-10px);
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0% {
    transform: translateY(0px);
  }

  50% {
    transform: translateY(-20px);
  }

  100% {
    transform: translateY(0px);
  }
}

.drop-shadow {
  filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.15));
}

.space-y-2>li:not(:last-child) {
  margin-bottom: 0.5rem;
}
</style>
