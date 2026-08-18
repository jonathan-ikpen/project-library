CREATE DATABASE IF NOT EXISTS dpls_db;
USE dpls_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'student') NOT NULL,
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    abstract TEXT NOT NULL,
    student_id INT,
    supervisor_id INT,
    category_id INT,
    supervisor_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    supervisor_note TEXT,
    admin_status ENUM('pending', 'published', 'rejected') DEFAULT 'pending',
    year INT NOT NULL,
    language VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FULLTEXT(title, abstract)
);

-- Files Table
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type ENUM('document', 'source_code', 'presentation', 'other') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Tags Table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- Project_Tags Junction
CREATE TABLE IF NOT EXISTS project_tags (
    project_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (project_id, tag_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- SEED DATA
-- ==========================================

-- Seed Categories
INSERT IGNORE INTO categories (id, name) VALUES 
(1, 'Web Development'),
(2, 'Data Science'),
(3, 'Mobile Applications'),
(4, 'Artificial Intelligence'),
(5, 'Cybersecurity'),
(6, 'Networking');

-- Seed Users (Passwords are 'password' hashed with BCRYPT)
-- admin@dpls.test, supervisor@dpls.test, student@dpls.test
INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES 
(1, 'System Admin', 'admin@dpls.test', '$2y$10$1d4QqJjgVs6aI5veHOklj.VtGtFPFko4aqrVeVuuE5RVMsWTA0N0e', 'admin'),
(2, 'Dr. Sarah Supervisor', 'supervisor@dpls.test', '$2y$10$1d4QqJjgVs6aI5veHOklj.VtGtFPFko4aqrVeVuuE5RVMsWTA0N0e', 'supervisor'),
(3, 'John Student', 'student@dpls.test', '$2y$10$1d4QqJjgVs6aI5veHOklj.VtGtFPFko4aqrVeVuuE5RVMsWTA0N0e', 'student');

-- Seed Projects
INSERT IGNORE INTO projects (id, title, abstract, student_id, supervisor_id, category_id, supervisor_status, admin_status, year, language) VALUES 
(1, 'AI-Powered Chatbot for Student Services', 'This research presents the development and deployment of an advanced, transformer-based conversational agent designed specifically to handle student inquiries within a university ecosystem. By fine-tuning a pre-trained Large Language Model (LLM) on a vast corpus of institutional data, policies, and academic calendars, the system achieves a 94% accuracy rate in intent recognition. The architecture implements a hybrid approach, combining retrieval-augmented generation (RAG) with a deterministic fallback mechanism for sensitive administrative operations. Empirical evaluation demonstrates a significant reduction in administrative workload, decreasing average ticket resolution time from 24 hours to under 3 minutes, while maintaining high user satisfaction metrics across a diverse student demographic.', 3, 2, 4, 'approved', 'published', 2026, 'Python, React'),
(2, 'Building Scalable Web Architecture', 'This thesis investigates the architectural paradigms required to transition traditional monolithic web services into highly distributed microservice ecosystems. Focusing on cloud-native deployments, the study evaluates the performance, latency, and fault-tolerance of containerized services orchestrated via Kubernetes. A comprehensive load-testing framework was developed to simulate high-concurrency traffic, revealing that while microservices introduce network overhead, the implementation of strategic caching layers and asynchronous event-driven communication mitigates latency spikes by up to 40%. The findings provide a prescriptive roadmap for enterprise software teams looking to adopt scalable, resilient cloud architectures without compromising data consistency.', 3, 2, 1, 'approved', 'published', 2025, 'Node.js, AWS'),
(3, 'Cyber Threat Intelligence Platform', 'As cyber threats grow increasingly sophisticated, the need for centralized, real-time threat intelligence becomes paramount. This project introduces a scalable Threat Intelligence Platform (TIP) that aggregates, correlates, and analyzes disparate indicators of compromise (IoCs) from multiple open-source and proprietary feeds. Utilizing machine learning algorithms, specifically Isolation Forests and Support Vector Machines, the system dynamically scores the severity of incoming threats and automates the dissemination of firewall rules to edge devices. Field testing within a simulated enterprise network showed a 60% improvement in threat detection time and a drastic reduction in false positive alerts compared to legacy SIEM solutions.', 3, 2, 5, 'approved', 'published', 2026, 'Python, Vue'),
(4, 'Distributed Systems Consensus Algorithms', 'Maintaining data consistency in highly distributed, partition-tolerant networks remains a critical challenge in modern computing. This paper provides a comparative analysis of the Paxos and Raft consensus algorithms when subjected to extreme network latency and packet loss. Through the development of a custom distributed systems emulator, we measured the leader election times, replication latency, and recovery protocols of both algorithms under Byzantine failure conditions. The results indicate that while Paxos offers theoretical optimality, Raft''s strong leader approach provides significantly faster recovery times in high-latency environments, making it more suitable for geographically dispersed database clusters.', 3, 2, 6, 'approved', 'published', 2024, 'Go'),
(5, 'Exploring Quantum Cryptography', 'The imminent arrival of practical quantum computing poses an existential threat to classical cryptographic systems based on integer factorization and discrete logarithms. This study explores the theoretical and practical frameworks of post-quantum cryptography (PQC), focusing on lattice-based cryptographic algorithms. We implemented a prototype secure communication channel utilizing the CRYSTALS-Kyber key encapsulation mechanism. Our performance benchmarking across various hardware architectures reveals that while lattice-based algorithms require larger key sizes, their computational overhead remains well within acceptable bounds for modern IoT devices, proving that a seamless transition to quantum-resistant security is feasible.', 3, 2, 5, 'approved', 'published', 2026, 'C++'),
(6, 'Facial Recognition Access Control', 'Access control systems increasingly rely on biometric authentication to enhance physical security. This project details the design and deployment of a real-time facial recognition system leveraging deep convolutional neural networks (CNNs). Built on the OpenCV framework and optimized for edge computing devices, the system performs liveness detection to prevent spoofing attacks via photographs or screens. The model was trained on a diverse dataset to mitigate demographic bias, achieving a 99.2% true positive rate under variable lighting conditions. The integration with existing physical turnstiles demonstrates the viability of deploying highly accurate, frictionless biometric security in high-traffic corporate environments.', 3, 2, 4, 'approved', 'published', 2025, 'Python, OpenCV'),
(7, 'Graph Neural Networks for Social Media', 'Social media platforms frequently act as echo chambers, polarizing user opinions through algorithmic content recommendation. This research applies Graph Neural Networks (GNNs) to model and predict user behavior and information diffusion across large-scale social graphs. By representing users as nodes and interactions as edges, our GNN model captures complex topological features that traditional predictive models overlook. The study analyzes a dataset of over 10 million interactions, successfully identifying isolated community clusters and predicting the viral trajectory of misinformation with an 85% accuracy rate. The findings underscore the potential of GNNs in developing more ethical, diverse content recommendation engines.', 3, 2, 4, 'approved', 'published', 2026, 'PyTorch'),
(8, 'Heuristic Search in Pathfinding', 'Pathfinding in massive, dynamic 3D environments is computationally expensive and critical for modern simulations and gaming engines. This paper proposes an optimized heuristic search algorithm that builds upon the traditional A* approach. By introducing a hierarchical navigation mesh and dynamic weight adjustments based on localized terrain density, the algorithm significantly reduces the search space. Empirical tests conducted within a highly complex virtual city environment demonstrate a 45% reduction in path computation time and a 30% decrease in memory consumption compared to standard A*, without any loss in path optimality. This optimization is particularly beneficial for resource-constrained mobile hardware.', 3, 2, 1, 'approved', 'published', 2023, 'C#'),
(9, 'Intelligent Tutoring Systems', 'The shift towards digital education necessitates the development of systems that can adapt to individual learning paces. This project presents an Intelligent Tutoring System (ITS) that leverages reinforcement learning to dynamically generate personalized educational pathways. The system continuously evaluates student performance through interactive assessments, using the resulting data to tailor the difficulty and topic selection of subsequent modules. A pilot study involving 200 undergraduate students showed a marked improvement in knowledge retention and engagement compared to static curriculum delivery. The scalable architecture, built with React and Node.js, allows for rapid integration into existing Learning Management Systems.', 3, 2, 4, 'approved', 'published', 2026, 'React, Node.js'),
(10, 'Secure File Sharing Protocol', 'Securing data transmission across untrusted networks requires robust cryptographic protocols. This project analyzes the vulnerabilities inherent in current file-sharing standards and proposes a custom, end-to-end encrypted file transfer protocol. Utilizing AES-256 for symmetric data encryption and RSA-4096 for secure key exchange, the protocol ensures data confidentiality and integrity. Furthermore, we implemented a Zero-Knowledge Proof (ZKP) authentication mechanism that allows clients to verify their identity without transmitting sensitive credentials over the network. Network simulations confirm that the protocol resists man-in-the-middle (MitM) and replay attacks, offering a highly secure alternative for enterprise data exchange.', 3, 2, 5, 'pending', 'pending', 2026, 'Go, C++');

-- Seed Tags
INSERT IGNORE INTO tags (id, name) VALUES 
(1, 'Machine Learning'), (2, 'NLP'), (3, 'Cryptography'), (4, 'Networks');

-- Seed Project Tags
INSERT IGNORE INTO project_tags (project_id, tag_id) VALUES 
(1, 1), (1, 2), (2, 3), (2, 4);

-- Seed Files
INSERT IGNORE INTO files (id, project_id, file_name, original_name, file_type, mime_type) VALUES 
(1, 1, 'dummy_document.pdf', 'Final_Project_Document_1.pdf', 'document', 'application/pdf'),
(2, 2, 'dummy_document.pdf', 'Final_Project_Document_2.pdf', 'document', 'application/pdf'),
(3, 3, 'dummy_document.pdf', 'Final_Project_Document_3.pdf', 'document', 'application/pdf'),
(4, 4, 'dummy_document.pdf', 'Final_Project_Document_4.pdf', 'document', 'application/pdf'),
(5, 5, 'dummy_document.pdf', 'Final_Project_Document_5.pdf', 'document', 'application/pdf'),
(6, 6, 'dummy_document.pdf', 'Final_Project_Document_6.pdf', 'document', 'application/pdf'),
(7, 7, 'dummy_document.pdf', 'Final_Project_Document_7.pdf', 'document', 'application/pdf'),
(8, 8, 'dummy_document.pdf', 'Final_Project_Document_8.pdf', 'document', 'application/pdf'),
(9, 9, 'dummy_document.pdf', 'Final_Project_Document_9.pdf', 'document', 'application/pdf'),
(10, 10, 'dummy_document.pdf', 'Final_Project_Document_10.pdf', 'document', 'application/pdf'),
(11, 1, 'dummy_presentation.pptx', 'Final_Presentation_1.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(12, 1, 'dummy_source.zip', 'Source_Code_1.zip', 'source_code', 'application/zip'),
(13, 2, 'dummy_presentation.pptx', 'Final_Presentation_2.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(14, 2, 'dummy_source.zip', 'Source_Code_2.zip', 'source_code', 'application/zip'),
(15, 3, 'dummy_presentation.pptx', 'Final_Presentation_3.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(16, 3, 'dummy_source.zip', 'Source_Code_3.zip', 'source_code', 'application/zip'),
(17, 4, 'dummy_presentation.pptx', 'Final_Presentation_4.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(18, 4, 'dummy_source.zip', 'Source_Code_4.zip', 'source_code', 'application/zip'),
(19, 5, 'dummy_presentation.pptx', 'Final_Presentation_5.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(20, 5, 'dummy_source.zip', 'Source_Code_5.zip', 'source_code', 'application/zip'),
(21, 6, 'dummy_presentation.pptx', 'Final_Presentation_6.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(22, 6, 'dummy_source.zip', 'Source_Code_6.zip', 'source_code', 'application/zip'),
(23, 7, 'dummy_presentation.pptx', 'Final_Presentation_7.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(24, 7, 'dummy_source.zip', 'Source_Code_7.zip', 'source_code', 'application/zip'),
(25, 8, 'dummy_presentation.pptx', 'Final_Presentation_8.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(26, 8, 'dummy_source.zip', 'Source_Code_8.zip', 'source_code', 'application/zip'),
(27, 9, 'dummy_presentation.pptx', 'Final_Presentation_9.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(28, 9, 'dummy_source.zip', 'Source_Code_9.zip', 'source_code', 'application/zip'),
(29, 10, 'dummy_presentation.pptx', 'Final_Presentation_10.pptx', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
(30, 10, 'dummy_source.zip', 'Source_Code_10.zip', 'source_code', 'application/zip');
