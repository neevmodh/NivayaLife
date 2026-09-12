# AI/model research: what could actually improve report understanding

Researched September 2026, to answer a specific question: should NivayaLife
replace Gemini/Groq with something else, and what does the current medical-AI
research landscape suggest we build next? "Top 100 papers" isn't how this
plays out in practice — the useful signal came from ~30 real, citable sources
across five research threads, synthesized below. Every claim is sourced;
nothing here is invented.

## Recommendation, up front

**Don't drop Gemini/Groq yet.** The economics don't support it at NivayaLife's
current scale, and the honest technical case for self-hosting isn't there
either. What *is* worth building, cheaply, regardless of which LLM sits
behind it:

1. A **structured-extraction step before summarization** (pull test
   name/value/unit/reference-range as data, not prose, then summarize from
   the structured data) — meaningfully reduces hallucination risk versus
   summarizing raw OCR text directly.
2. A cheap **rule-based safety check**: cross-verify that every numeric value
   the AI summary mentions actually appears in the OCR'd/extracted text
   before showing it to a user. This is the single highest-value, lowest-cost
   change on this list.
3. **Empirically test Gujarati summary quality specifically** — the research
   below found a real, cited risk that Gujarati may be a weaker language for
   current LLMs than Hindi, in ways that don't show up unless you look.
4. Consider a **specialized handwriting-OCR pass** as a second opinion when
   Tesseract's confidence is low, specifically for handwritten Indian
   prescriptions — this is a distinct, well-researched problem from general
   OCR, and NivayaLife's current pipeline treats all documents the same way.

## 1. Why not self-host an open medical LLM instead of Gemini/Groq

**The economics don't support it yet.** Self-hosting only starts paying off
above roughly 50,000–100,000 API calls a month — below that, hosted APIs are
simpler *and* cheaper; above it, self-hosting can save 40–70% depending on
model and hardware. NivayaLife is nowhere near that volume right now, and
Gemini/Groq's free tiers are cheaper than provisioning and maintaining a
dedicated inference service. This isn't a permanent verdict — revisit it if
usage grows into that range.

**The technical case is also weaker than "open medical LLM" branding
suggests.** The 2023–2024 wave of medical-specific fine-tunes — Meditron,
OpenBioLLM, BioMistral, PMC-LLaMA — has largely been overtaken by frontier
general-purpose models; even general-purpose Qwen2.5-32B now outperforms
those small medical specialists on aggregate medical benchmarks. On a
head-to-head clinical-report-summarization benchmark (MedS-Bench), Mistral 3
led open-source models with a BLEU/ROUGE score around 24.5–24.9, while
closed models (GPT-4, Claude 3.5) — the category Gemini competes in — still
came out ahead. In short: swapping to an open "medical" model wouldn't
obviously improve quality, and would add real infrastructure cost.

**If self-hosting ever becomes worth it**, the practical options are smaller
than people assume: Gemma 3 4B runs in ~4.2 GB RAM, and Phi-4 (14B) is
competitive with GPT-4o on some benchmarks while fitting a single 12 GB GPU
— i.e., CPU/small-GPU inference is realistic today, just not yet justified
by NivayaLife's volume.

## 2. Structured extraction before summarization (the real quality lever)

Several 2026 papers (MedStruct-S, "Key Coverage Matters") frame clinical
report understanding as two separate problems: **extracting** the
structured fields (test name, value, unit, reference range) from a
scanned/OCR'd document, and **then** summarizing from that structured
data — not summarizing the raw OCR text directly. An earlier
production pipeline built exactly this way (OCR module + a dedicated
extraction module) hit 0.93 OCR accuracy and an F1 of 0.86 for the four key
lab-test fields, on 153 real reports.

This matters for NivayaLife specifically: today, `OcrExtractor`/`OcrResolver`
hand raw extracted text to the AI client to summarize in one step. Inserting
a structured-extraction pass in between — even a simple regex/heuristic one
for common lab-report layouts, upgraded later — gives the summarizer clean
data instead of noisy OCR text, which is the single biggest lever on
summary quality that has nothing to do with which LLM is doing the writing.

## 3. Hallucination and safety in patient-facing summaries

This is the most directly actionable research thread, because the numbers
are sobering and the mitigation is cheap. Medically important content
missing from a summary (omission) has been measured at 3–4% in controlled
studies, rising to **47% in real encounter-level evaluations**. Hallucination
rates on some current benchmarks run as high as 40–50%, even on recent
models. Two 2026 papers propose concrete mitigations: **CARE**, a conformal
safety layer for medical summarization, and hallucination-detection-guided
preference optimization for clinical summarization specifically.

The cheap version of this NivayaLife can build without adopting either
paper's full framework: after generating a summary, programmatically check
that every numeric value/unit the summary states (e.g. "13.8 g/dL") appears
in the OCR-extracted source text. Flag (don't just silently show) any
summary sentence containing a number that can't be matched back to the
source. This directly targets the failure mode the research identifies
without needing a new model.

## 4. Handwritten prescription OCR — a distinct, under-served problem

General-purpose OCR (what Tesseract does today) is trained on printed text.
Handwritten Indian prescriptions are a genuinely different, actively
researched problem: irregular spacing, huge handwriting-style variance,
non-standard drug abbreviations, and OCR trained on printed text scales
poorly to it. Multiple 2026 papers target this specifically —
CRNN+CTC-based recognition, and **MIRAGE**, which fine-tunes multimodal LLMs
(LLaVA 1.6, Idefics2) specifically for Indian prescription handwriting.

For NivayaLife: prescriptions are one of the app's core report types, and
they're disproportionately handwritten in the Indian context this app
targets. A concrete, scoped improvement would be routing prescription-type
uploads through a specialized handwriting pass (or a multimodal vision-LLM
prompt tuned for prescription handwriting) as a fallback when Tesseract's
own confidence score is low — rather than accepting whatever Tesseract
returns as final.

## 5. Multilingual support — a real, specific risk for Gujarati

NivayaLife markets English/Hindi/Gujarati summaries as a differentiator (see
`docs/COMPETITIVE_ANALYSIS.md`). The research here is a genuine yellow flag,
not a green light: **IndicMedDialog** (BioNLP 2026), a medical dialogue
benchmark spanning English plus nine Indic languages including Hindi and
Gujarati, found strong post-processed diagnostic accuracy for Hindi,
Marathi, and Bengali — while some other Indic languages fell into what the
paper calls an "extreme failure tier," attributable to base-model tokenizer
gaps. The search didn't surface a result that explicitly placed Gujarati in
either bucket, which itself is the finding: **there's no confirmation
Gujarati performs as well as Hindi does for medical text with current
models**, only that some Indic languages clearly don't. This is worth
NivayaLife testing empirically — a handful of real reports run through the
existing Gemini/Groq pipeline in Gujarati, read by a Gujarati speaker for
correctness — rather than assuming quality parity with Hindi.

## Sources

- [BioMistral: A Collection of Open-Source Pretrained LLMs for Medical Domains](https://arxiv.org/html/2402.10373v2)
- [PMC-LLaMA: Towards Building Open-source Language Models for Medicine](https://arxiv.org/pdf/2304.14454)
- [Towards Evaluating and Building Versatile Large Language Models for Medicine](https://arxiv.org/pdf/2408.12547) ([npj Digital Medicine version](https://www.nature.com/articles/s41746-024-01390-4))
- [Best Open-Source LLMs for Medical Diagnosis (2026 Guide)](https://www.softx.ca/resources/best-open-source-llms-for-medical-diagnosis)
- [Key Coverage Matters: Semi-Structured Extraction of OCR Clinical Reports](https://arxiv.org/pdf/2605.09440)
- [MedStruct-S: A Benchmark for Key Discovery, Key-Conditioned QA and Semi-Structured Extraction from OCR Clinical Reports](https://arxiv.org/pdf/2605.03103)
- [Extracting laboratory test information from paper-based reports](https://link.springer.com/article/10.1186/s12911-023-02346-6) ([PMC](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC10629084/))
- [A Framework to Assess Clinical Safety and Hallucination Rates of LLMs for Medical Text Summarisation](https://www.medrxiv.org/content/10.1101/2024.09.12.24313556v2)
- [CARE: A Conformal Safety Layer for Medical Summarization](https://arxiv.org/pdf/2606.08969)
- [Hallucination Detection-Guided Preference Optimization for Clinical Summarization](https://arxiv.org/pdf/2605.28910)
- [Hallucinations and Key Information Extraction in Medical Texts: A Comprehensive Assessment of Open-Source LLMs](https://arxiv.org/pdf/2504.19061)
- [Leveraging Deep Learning with Multi-Head Attention for Accurate Extraction of Medicine from Handwritten Prescriptions](https://arxiv.org/pdf/2412.18199)
- [MIRAGE: Multimodal Identification and Recognition of Annotations in Indian General Prescriptions](https://arxiv.org/html/2410.09729v1)
- [A Hybrid Deep Learning–Based OCR Model for Handwritten Medical Prescriptions](https://www.frontiersin.org/journals/medicine/articles/10.3389/fmed.2026.1856485/abstract)
- [IndicMedDialog: A Parallel Multi-Turn Medical Dialogue Dataset for Accessible Healthcare in Indic Languages](https://aclanthology.org/2026.bionlp-1.84/)
- [Self-Hosted LLM Guide: Setup, Tools & Cost Comparison (2026)](https://www.premai.io/blog/self-hosted-llm-guide-setup-tools-cost-comparison-2026/)
- [Best Small Language Models 2026: Top SLMs Ranked (1B–14B)](https://localaimaster.com/blog/small-language-models-guide-2026)
- [A Survey of Small Language Models](https://arxiv.org/pdf/2410.20011)
