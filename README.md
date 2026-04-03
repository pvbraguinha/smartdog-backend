

# 🐾 SmartDog — Biometric Snout Recognition for Dogs & Cats

> A computer vision system for biometric identification of dogs and cats using nose print recognition, built to support animal welfare, population control, and zoonosis surveillance under the **One Health** framework.

---

## 🧠 Overview

Animal identification is a critical challenge for municipalities, NGOs, and public health agencies managing stray populations and zoonotic disease risk. SmartDog addresses this by using **convolutional neural networks (CNNs)** to recognize individual animals through their unique nose prints — a biometric as distinctive as a human fingerprint.

This project was developed as part of **Petimuni**, a digital platform designed to support municipal animal welfare departments and zoonosis surveillance units in Brazil.

---

## 🔬 How It Works

1. A photo of the animal's snout is captured via the mobile/web frontend
2. The image is sent to the backend API (hosted on **Render**)
3. A CNN model compares the snout against a registered database
4. The system returns a match confidence score and animal profile

---

## 🏗️ Architecture

```
Frontend (Bolt)
      │
      ▼
REST API (PHP/Laravel — Render)
      │
      ▼
CNN Model (Python — trained on Google Colab)
      │
      ▼
Database + Image Storage (Amazon AWS)
```

---

## 🤖 Machine Learning Model

- **Architecture:** Convolutional Neural Network (CNN)
- **Training environment:** Google Colab (GPU)
- **Datasets used:**
  - [Stanford Dogs Dataset](http://vision.stanford.edu/aditya86/ImageNetDogs/) — 20,580 images, 120 breeds
  - [Oxford-IIIT Pet Dataset](https://www.robots.ox.ac.uk/~vgg/data/pets/) — 7,349 images, 37 breeds
- **Task:** Biometric snout matching (identity verification, not breed classification)
- **Key technique:** Feature extraction + similarity comparison between snout embeddings

---

## 🌍 Real-World Context

This system was developed to solve a concrete public health problem:

- Brazil has an estimated **30 million stray animals**
- Traditional microchip identification requires physical capture
- Municipalities lack scalable tools for population tracking
- Uncontrolled animal populations are a primary vector for zoonotic diseases (rabies, leishmaniasis, leptospirosis)

SmartDog enables **non-invasive remote identification** using only a photograph, making it viable for field use by animal control agents and veterinary teams.

---

## 🚀 Deployment

| Component | Technology |
|-----------|-----------|
| Frontend | Bolt |
| Backend API | PHP · Render (403 deployments) |
| ML Training | Python · Google Colab |
| Storage & Infrastructure | Amazon AWS |
| Database | MySQL (Replicate integration) |

---

## 📁 Repository Structure

```
smartdog-backend/
├── app/              # Core application logic
├── bootstrap/        # App initialization
├── config/           # Configuration files
├── database/         # Migrations and seeders
├── public/           # Public assets
├── resources/        # Views and templates
├── routes/           # API route definitions
├── storage/          # File storage
└── tests/            # Automated tests
```

---

## 👨‍💻 Author

**Paulo Victor Braga de Almeida Santos**  
Veterinarian | Startup Founder | Aspiring AI Researcher  
[LinkedIn](https://linkedin.com/in/paulo-victor-braga-084b17177) · pvbraguinha@gmail.com  
Coimbra, Portugal

---

## 🔗 Related Work

- *Phlebotomic fauna and seroprevalence for canine visceral leishmaniasis in an urban area in the Midwest region of Brazil* — Arquivo Brasileiro de Medicina Veterinária e Zootecnia, 2020
- *Epidemiology of Norovirosis and Study of the Role of the Dog as a Reservoir for this Zoonotic Agent* — UNICIÊNCIAS, 2019

---

> *"The protection of human health begins with the health of animals and the environments we share."*  
> — One Health Initiative
