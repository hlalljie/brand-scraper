import { JSX, useState } from "react";
import chroma from 'chroma-js';
import { Loader2 } from 'lucide-react'


class ResultData {
    error?: Error;
    received?: string;
    brandData?: BrandData;
    parsedData?: string;

    constructor(resData: any) {
        resData.error && (this.error = resData.error);
        resData.received && (this.received = resData.received);
        resData.brandData &&
            (this.brandData = new BrandData(resData.brandData));
        resData.parsedData && (this.parsedData = resData.parsedData);
    }
}

class BrandData {
    colors?: ColorData;
    fonts?: FontData;

    constructor(brandData: Record<string, any>) {
        brandData.colors && (this.colors = brandData.colors as ColorData);
        brandData.fonts && (this.fonts = brandData.fonts as FontData);
    }
}

interface ColorData {
    [color: string]: string[];
}

interface FontData {
    [font: string]: string[];
}

const Home = (): JSX.Element => {
    const [input, setInput] = useState("");
    const [currentSite, setCurrentSite] = useState("");
    const [loading, setLoading] = useState(false);
    const [resData, setResData] = useState(null);

    const handleSearch = () => {
        const fetchAddress = "/api/find-styles";
        // const fetchAddress = "api/test";

        const tempInput = input;
        setCurrentSite(tempInput);
        setLoading(true);
        setInput("");
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input, testNumber: 0, loadTime: 1 }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(new ResultData(data) as React.SetStateAction<null>);
                setLoading(false);
            });
    };

    return (
        <div id='main' className="background-gradient animate-gradient-x-slow relative h-screen grid grid-rows-[auto_1fr_auto]" >
            <section id='header' className="p-3">
                <h1>Style Finder</h1>
            </section>
            {(!resData) ?
                <section id='content-container' className="max-w-md mt-[30vh]">
                    {
                        loading ? <Loading currentSite={currentSite} /> :
                            <h2 id="intro" className='text-center heading-gradient'>Search a website for its brand colors and fonts.</h2>
                    }


                </section> :
                <section id='content-container' className="pt-10 max-w-2xl w-full">
                    {resData ? <ResultsDisplay resData={resData} loading={loading} /> : null}
                    {loading && <Loading withContent currentSite={currentSite} />}
                </section>
            }
            <InputContainer input={input} setInput={setInput} handleSearch={handleSearch} />
        </div >
    );
};

const Loading = ({ withContent, currentSite = "" }: { withContent?: boolean, currentSite?: string }): JSX.Element => {
    return <div id="loading-container" className="text-center">
        {<h3 className={"heading-gradient mb-4" + (withContent ? " mt-10" : "")}>Parsing Site Content for {currentSite}</h3>}
        <svg
            className="animate-spin mx-auto"
            width="40"
            height="40"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <linearGradient id="loader-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" />
                    <stop offset="100%" />
                </linearGradient>
            </defs>
            <Loader2 stroke="url(#loader-gradient)" strokeWidth="2" />
        </svg>
    </div>;
};

const InputContainer = ({ input, setInput, handleSearch }: { input: string, setInput: React.Dispatch<React.SetStateAction<string>>, handleSearch: () => void }): JSX.Element => {
    return (
        <section id='input-container' className="w-full ">
            <div id='input ' className="mx-auto w-fit p-7">
                <input
                    className="rounded-tl-sm rounded-bl-sm max-w-sm bg-inputcolor w-screen px-4 py-2 text-lg focus:outline-none"
                    type="text"
                    placeholder="Enter a website URL"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                ></input>
                <button id='search' className={
                    (input != "" ? 'bg-inputreadycolor ' : 'bg-inputbtncolor ') + 'rounded-tr-sm rounded-br-sm px-4 py-2 text-lg text-gray-200 hover:text-white'} onClick={handleSearch}
                >
                    <a className={input != "" ? "heading-gradient" : ""}>Search</a>
                </button>
            </div>
        </section >
    )
}

const ResultsDisplay = ({ resData, loading }: { resData: ResultData, loading?: boolean }): JSX.Element => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                !loading && (<ErrorDisplay error={resData.error}
                />)
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }: { error: Error }): JSX.Element => {
    return (
        <div id="error-display" className="mt-[30vh]">
            <h3 className="text-center heading-gradient">{"Error: " + error.toString() + "."} < br /> Try Again</h3>
        </div>
    );
};

const DataDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div id="data-display">
            <h3 className="text-center">Website Styles for {resData.received}</h3>
            {resData.brandData!.colors ? (
                <ColorDisplay colors={resData.brandData!.colors} />
            ) : null}
            {resData.brandData!.fonts ? (
                <FontDisplay fonts={resData.brandData!.fonts} />
            ) : null}
            {resData.parsedData && (<ParsedDataDisplay parsedData={resData.parsedData} />)}

        </div>
    );
};

const ColorDisplay = ({ colors }: { colors: ColorData }): JSX.Element => {

    return (
        <div id="color-display" className="mt-10">
            <div id='color-container' className="flex flex-wrap justify-space-between gap-8">
                {Object.entries(colors).map((color) => (
                    <ColorPanel key={color[0]} color={color} />
                ))}
            </div>

        </div>
    );
};

const ColorPanel = ({ color }: { color: [string, string[]] }): JSX.Element => {
    const colorName = color[0];
    const colorLocs = color[1];
    const textColor = chroma(colorName).luminance() > 0.5 ? "black" : "white";
    return (
        <div
            id="color-panel"
            className="rounded-sm w-[calc(50%-1rem)] aspect-[2/1] flex flex-col items-center justify-center gap-2"
            style={{ backgroundColor: colorName }}
        >
            <h5 id="color-name" style={{ color: textColor }}>{colorName}</h5>
            <p style={{ color: textColor }}>{colorLocs.join(", ")}</p>
        </div >
    )
}

const FontDisplay = ({ fonts }: { fonts: FontData }): JSX.Element => {
    return (
        <div id="font-display" className="mt-10 text-left">
            <h3>Fonts:</h3>
            {Object.entries(fonts).map(([key, values]) => (
                <h4 key={key}>
                    <strong>{key}</strong> - {values.join(", ")}
                </h4>
            ))}

        </div>
    );
};

const ParsedDataDisplay = ({
    parsedData,
    visible
}: {
    parsedData: string;
    visible?: boolean;
}): JSX.Element => {
    return (
        <div id="parsed-data-container" className={visible ? "" : "hidden"}>
            <h3>All Parsed Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
