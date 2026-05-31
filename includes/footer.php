<footer class="footer bg-[var(--white)] border-t border-[var(--border)] text-[var(--text-dark)]">
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Top -->
        <div class="grid gap-8 py-10 md:grid-cols-2 lg:grid-cols-5">

            <!-- About -->
            <div class="lg:col-span-2">

                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[var(--primary)] mb-2">
                    ABOUT JOBDZ
                </p>

                <h3 class="text-xl font-bold mb-3">
                    Your career starts here.
                </h3>

                <p class="text-sm text-[var(--text-gray)] leading-relaxed mb-5 max-w-md">
                    JobDZ connects ambitious professionals with top employers across Algeria.
                    Find your next opportunity with confidence.
                </p>

                <!-- Social -->
                <div class="flex gap-3">

                    <a href="#"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--bg-purple-light)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                        <i class="fab fa-facebook-f text-sm"></i>
                    </a>

                    <a href="#"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--bg-purple-light)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                        <i class="fab fa-twitter text-sm"></i>
                    </a>

                    <a href="#"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--bg-purple-light)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                        <i class="fab fa-linkedin-in text-sm"></i>
                    </a>

                    <a href="#"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--bg-purple-light)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>

                </div>

            </div>

            <!-- Candidates -->
            <div>

                <h4 class="text-sm font-bold uppercase tracking-[0.2em] mb-4">
                    Candidates
                </h4>

                <ul class="space-y-2.5 text-sm">

                    <li>
                        <a href="register.php?role=candidate"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Create account
                        </a>
                    </li>

                    <li>
                        <a href="jobs.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Browse jobs
                        </a>
                    </li>

                    <li>
                        <a href="saved_jobs.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Saved jobs
                        </a>
                    </li>


                </ul>

            </div>

            <!-- Companies -->
            <div>

                <h4 class="text-sm font-bold uppercase tracking-[0.2em] mb-4">
                    Companies
                </h4>

                <ul class="space-y-2.5 text-sm">

                    <li>
                        <a href="register.php?role=company"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Post a job
                        </a>
                    </li>

                    <li>
                        <a href="companies.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Explore companies
                        </a>
                    </li>

                    <li>
                        <a href="jobs.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Manage jobs
                        </a>
                    </li>

                </ul>

            </div>

            <!-- Support -->
            <div>

                <h4 class="text-sm font-bold uppercase tracking-[0.2em] mb-4">
                    Support
                </h4>

                <ul class="space-y-2.5 text-sm">

                    <li>
                        <a href="about.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            About us
                        </a>
                    </li>

                    <li>
                        <a href="contact.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            Contact us
                        </a>
                    </li>

                    <li>
                        <a href="faq.php"
                            class="text-[var(--text-gray)] transition hover:text-[var(--primary)]">
                            FAQ
                        </a>
                    </li>

                </ul>

            </div>

        </div>

        <!-- Bottom -->
        <div class="border-t border-[var(--border)] py-5 flex flex-col sm:flex-row items-center justify-between gap-3">

            <p class="text-xs text-[var(--text-gray)]">
                © <?php echo date('Y'); ?> JobDZ. All rights reserved.
            </p>

         

        </div>

    </div>
</footer>